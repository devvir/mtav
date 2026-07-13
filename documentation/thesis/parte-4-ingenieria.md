```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Parte IV — Ingeniería del sistema

*Esta parte profundiza en las decisiones de ingeniería que sostienen la aplicación: cómo está organizada, cómo se mantiene el sorteo como una pieza independiente, cómo se resuelven la autorización y el aislamiento entre cooperativas, y cómo los datos cruzan del backend al frontend. A diferencia de las Partes I y II —suficientes para comprender qué es y qué hace MTAV—, esta parte está dirigida a quien quiera entender cómo está construido. El detalle de referencia (esquemas completos, glosarios, diagramas) se remite a los apéndices.*

---

## 18. Arquitectura de la aplicación

MTAV en línea es un **monolito full-stack**: un único proyecto que contiene el backend (Laravel) y el frontend (Vue), unidos por Inertia.js, sin una API REST o GraphQL intermedia. La justificación de esta elección —y sus compromisos— se discutió en la Sección 5; aquí interesa su consecuencia arquitectónica: los controladores de Laravel resuelven cada petición devolviendo directamente el componente Vue de la página con sus datos, y el servidor es la única fuente de verdad.

Internamente, el backend se organiza en **capas** con responsabilidades separadas: la capa HTTP (controladores, *form requests* de validación y políticas de autorización), una capa de **servicios** que concentra la lógica de dominio (por ejemplo, `LotteryService` para el sorteo), y los modelos Eloquent que median el acceso a la base de datos. Los controladores quedan delgados: reciben la petición, delegan en un servicio y devuelven una respuesta.

Un principio transversal es el **desacople por eventos**. Las acciones del dominio emiten eventos de Laravel, y *listeners* independientes se ocupan de los efectos secundarios —enviar notificaciones, difundir actualizaciones en tiempo real por WebSocket (Reverb)—. Por ejemplo, la ejecución del sorteo emite eventos (`GroupLotteryExecuted`, `ProjectLotteryExecuted`) que disparan, por separado, la creación de notificaciones y el *broadcast* a los clientes conectados. Así, la lógica central no conoce ni depende de esos efectos: agregar o modificar una notificación no toca el código del sorteo.

Todo esto corre sobre el entorno **Docker** multiservicio introducido en la Sección 5 —PHP, servidor web, base de datos, servidor de tiempo real y compilación de recursos, cada uno en su contenedor—. Ese entorno resuelve la paridad entre desarrollo y producción, pero plantea a la vez un problema de **experiencia de desarrollo (DX)**: coordinar varios servicios, separar los distintos entornos (desarrollo, pruebas, producción) y encadenar sin equivocarse las operaciones habituales —levantar todo, migrar, sembrar datos, correr las pruebas— es complejo y frágil. Dejar esa complejidad expuesta sería una fuente permanente de fricción y de errores para quien retome el proyecto.

Por eso la infraestructura se trató como parte del producto, y no como un accesorio. Los servicios se definen de forma genérica y los **entornos** se arman componiéndolos —desarrollo con recarga en vivo, pruebas aisladas en memoria, ejecución extremo a extremo, y una composición de producción con imágenes autocontenidas—, cada uno aislado y capaz de convivir con los demás. Sobre esa base, un único comando, `./mtav`, unifica toda la operación: desde un clon limpio, `mtav up` crea la configuración, construye las imágenes, instala dependencias, migra y siembra la base, y deja la aplicación funcionando —un solo comando en lugar de la larga secuencia de pasos manuales que exigiría hacerlo a mano—. El objetivo es explícito: que quien tome el proyecto pueda empezar a trabajar en minutos y operar sin riesgo, sin necesidad de dominar los detalles de Docker. Esta capa de herramientas y entornos se detalla en el Apéndice I.

---

## 19. El sorteo como capa independiente

El módulo del sorteo está diseñado como una **capa casi portátil**, deliberadamente desacoplada del resto de la aplicación. Dos decisiones lo hacen posible.

Primero, el sorteo se resuelve detrás de una interfaz de **estrategia** (`SolverInterface`): los distintos *solvers* —el basado en GLPK para producción, y otros auxiliares (aleatorio y de prueba)— son intercambiables y se seleccionan por configuración. La lógica que orquesta el sorteo depende de la interfaz, no de un *solver* concreto. Esto es *plug-and-play*: se puede sustituir el algoritmo sin tocar el resto del sistema. La ventaja es triple —**testeabilidad** (se usan *solvers* de prueba deterministas en los tests), **extensibilidad** (incorporar un nuevo enfoque, como la programación por restricciones propuesta por Fierro, es agregar un *solver*) y **claridad** (el algoritmo queda aislado)—.

Segundo, el orquestador del sorteo opera sobre **datos crudos** (listas de identificadores), no sobre modelos Eloquent. El acoplamiento con el resto de la aplicación es mínimo: la lógica de asignación recibe familias, unidades y preferencias como estructuras simples y devuelve asignaciones, sin conocer la base de datos ni el framework. Esta independencia es lo que permitió, entre otras cosas, medir el algoritmo de forma aislada (Parte III, §15).

---

## 20. Autorización y alcance de proyecto

MTAV atiende a muchas cooperativas desde una misma instalación, de modo que dos garantías son críticas: que cada usuario solo pueda hacer lo que su rol permite, y que los datos de una cooperativa nunca se filtren a otra. Ambas se aplican en **varias capas, de forma redundante y deliberada** (defensa en profundidad).

**Autorización por políticas.** Cada acción sobre cada recurso se autoriza a través de una clase *Policy*. Por ejemplo, en la política de familias, crear y eliminar exige ser administrador, mientras que modificar lo puede hacer un administrador o un cooperativista sobre su propia familia. Sobre estas políticas hay un atajo: `Gate::before` concede todo a los superadministradores, evitando repetir esa excepción en cada política.

**Alcance de proyecto.** Casi todos los modelos llevan un *global scope* (`ProjectScope`) que filtra automáticamente las consultas al proyecto activo, resuelto en cada petición. La consecuencia es que una consulta **no puede** devolver datos de otra cooperativa aunque el código lo pidiera: el aislamiento se garantiza a nivel de la consulta, no en los controladores, donde sería fácil olvidarlo.

**Roles en capas.** El sistema de tres roles se aplica simultáneamente en el *scope* de consulta, en la política y en el controlador. Esta redundancia es intencional: si una capa falla o se omite por error, otra sigue protegiendo.

**La clave `can`.** Cada recurso serializado hacia el frontend incluye, para el usuario actual, qué acciones puede realizar sobre ese objeto (por ejemplo, si puede editarlo o eliminarlo). Así, la interfaz sabe qué mostrar o habilitar sin necesidad de consultas adicionales, y la decisión de permisos vive en un solo lugar —las políticas (Laravel Policies) del backend—.

---

## 21. Manejo de modelos y recursos

Un punto de fricción habitual en las aplicaciones full-stack es cómo viajan los datos del backend al frontend: normalmente hay que escribir, para cada modelo, código de serialización que decida qué campos exponer y con qué forma. En MTAV ese trabajo está automatizado.

Los modelos se convierten a una estructura JSON consistente de forma automática: devolver un modelo desde un controlador produce un recurso bien formado, sin escribir serialización a mano para cada uno. El mismo mecanismo admite **subconjuntos** de recurso —distintos niveles de detalle según el contexto— y **embebe los permisos** del usuario sobre cada objeto (la clave `can` descrita en §20). El resultado es que los datos cruzan del backend al frontend de forma transparente: la misma forma de modelo, con sus permisos, llega a los componentes Vue a través de Inertia.

En la dirección opuesta —del frontend al backend— ocurre algo análogo con los **formularios**. En lugar de definir a mano, para cada modelo, un esquema de formulario en el cliente, la especificación de cada formulario se **genera a partir de las reglas de validación** del backend (los *form requests* de la Sección 18): campos, tipos de control y restricciones se derivan de las mismas reglas que validarán el envío. Las reglas de validación son así la única fuente de verdad: un cambio en ellas se refleja a la vez en el formulario que ve el usuario y en la validación que lo protege.

Estas dos capacidades —la generación de formularios a partir de las reglas de validación y la serialización automática de recursos— se extrajeron a dos pequeños paquetes reutilizables que pueden resultar útiles en otros proyectos, como aporte a la comunidad de código abierto. Su superficie completa se documenta en el Apéndice A (glosario de recursos JSON).

---

## 22. Conclusiones y trabajo futuro

Este capítulo cierra el trabajo: evalúa lo logrado frente a lo planteado (§22.1) y organiza las líneas de trabajo futuro por área temática (§22.2 a §22.8).

### 22.1 Conclusiones

Este trabajo se propuso llevar un método de asignación probado —el algoritmo de MTAV, validado en proyectos cooperativos reales— desde una herramienta de escritorio que requería asistencia técnica hasta una plataforma de autogestión al alcance de cualquier cooperativa. Ese objetivo, planteado explícitamente como trabajo futuro en la investigación previa sobre MTAV (el "MTAV Online" de Fierro, 2024), se cumplió: MTAV en línea existe, funciona, y cubre el ciclo completo que esta tesis describe —desde la creación del proyecto y la incorporación de las familias hasta la ejecución auditada del sorteo y la publicación de su resultado.

De lo planteado a lo construido, el balance es el siguiente:

- **La plataforma.** Una aplicación web y móvil completa, con tres roles y acceso por invitación, preferencias familiares privadas con ajuste dinámico, plano interactivo, eventos, medios compartidos, notificaciones en tiempo real, dos idiomas, y accesibilidad como requisito de primer orden (Partes II y IV). La operación que antes exigía un intermediario técnico —instalar, recolectar planillas, consolidar a mano, ejecutar, comunicar— hoy es un flujo de autogestión de principio a fin.
- **El aporte algorítmico.** La Fase 1 del método original, su único punto de fragilidad, fue reemplazada por una búsqueda binaria sobre la cota de equidad, con equivalencia demostrada formalmente y verificada empíricamente contra la implementación original (Parte III). El resultado práctico está cuantificado: instancias que antes expiraban o no terminaban —incluidos los escenarios degenerados— hoy se resuelven al 100 %, sin un solo caso fallido hasta tamaños un orden de magnitud mayores que cualquier proyecto real (§15, Apéndice C.3).
- **La verificabilidad.** Cada ejecución del sorteo deja una traza de auditoría reproducible —entrada congelada, artefactos del solver, resultado— que convierte la confianza en el proceso en una propiedad verificable y no en un acto de fe (§17, Apéndice C).
- **La calidad como proceso.** El sistema se construyó con una suite de pruebas en capas, puertas de calidad automatizadas y documentación de usuario por rol integrada en la propia aplicación (Apéndices E y H).

Corresponde también señalar lo que este trabajo **no** alcanzó a hacer, y que define el paso inmediato: la plataforma **no está aún desplegada en producción ni validada con cooperativas reales**. El algoritmo hereda la validación de campo del MTAV original, pero la plataforma que lo rodea —la experiencia de autogestión que constituye la promesa central de este trabajo— debe todavía probarse con usuarios reales, en un sorteo real. Esa validación es la primera prioridad de la etapa siguiente.

[NOTA: autocrítica y dificultades encontradas — la guía del informe pide evaluar qué se planteó vs. qué se hizo, dificultades, y autocrítica de lo que faltó (tiempo, recursos, prioridades). Este es territorio de Diego: ¿qué fue más difícil de lo esperado? ¿qué harías distinto? ¿qué quedó afuera por tiempo?]

[NOTA: gestión del proyecto ("si aplica", dice la guía) — metodología de trabajo, etapas, uso de IA (remite al Apéndice F si alcanza con eso). Decidir si se incluye un párrafo acá.]

**Trabajo futuro.** Varias líneas de trabajo quedan fuera del alcance de esta tesis pero mejorarían la utilidad o la calidad de la plataforma. Se organizan por área temática.


### 22.2 Comunicación y comunidad

Un canal de mensajería o foro interno para que los cooperativistas se comuniquen dentro del proyecto —hoy esa comunicación ocurre en herramientas externas—, un sistema de anuncios enriquecidos con adjuntos, y la integración con medios externos (por ejemplo, notificaciones por WhatsApp o correo masivo). Es la continuación natural de la naturaleza compartida descrita en la Parte I.

### 22.3 El plano espacial

Completar y enriquecer el editor de plano: **redimensionar y reformar** unidades (mover los vértices de los polígonos), **soporte multinivel** para proyectos de varios pisos (navegación entre plantas, ya contemplado a nivel de datos), representación de superficies reales en metros cuadrados, exportación del plano a imagen o PDF, e importación de planos arquitectónicos existentes.

### 22.4 Rendimiento y escalabilidad

Una capa de caché para consultas frecuentes, optimización del solver para proyectos de gran escala, y paginación o virtualización en listas largas de unidades o familias.

### 22.5 Integración con sistemas externos

Exportación de los resultados del sorteo en formatos legales o notariales, integración con registros públicos de cooperativas, y APIs para sistemas de gestión de obra o de administración cooperativa.

### 22.6 Analítica, reportes y transparencia

Histórico de proyectos y comparación entre sorteos; **estadísticas de satisfacción agregadas** —métricas que reflejen qué tan bueno fue el resultado alcanzado, hoy inexistentes—; y reportes exportables para asambleas o auditorías externas. En esta línea, una mejora concreta de transparencia es permitir que, una vez ejecutado el sorteo, el administrador **habilite la visualización de las preferencias de todas las familias** para el conjunto de los socios, de modo que cualquiera pueda verificar el resultado contra las preferencias ingresadas (hoy cada familia ve solo las propias).

### 22.7 Mejoras al algoritmo y al proceso de asignación

El trabajo previo sobre MTAV dejó planteadas varias extensiones al algoritmo, exploradas en el proyecto de grado de Marcos Fierro (2024): un **criterio adicional de equidad** basado en la desviación estándar de las satisfacciones, con la posibilidad de elegir sobre un frente de Pareto entre mayor equidad y mayor satisfacción global; la incorporación de **preferencias de vecindad** (que una familia valore quedar cerca de otra determinada); y las **preferencias "en bloque"** (igualar la prioridad de varias unidades indiferentes). A ellas se suman otras posibilidades: restricciones adicionales —por ejemplo, reservar unidades en planta baja para familias con necesidades de accesibilidad—, la ponderación de las preferencias por intensidad (no solo por orden), y la ejecución de sorteos parciales o por etapas. Estas mejoras se relacionan directamente con la Parte III.

### 22.8 Experiencia de usuario y accesibilidad avanzada

Soporte completo de lectores de pantalla y un modo offline que permita navegar la aplicación y consultar la información ya cargada —el plano, los eventos, las preferencias propias— sin conexión, útil en zonas con conectividad limitada.
