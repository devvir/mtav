```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Ingeniería del sistema {#cap-ingenieria}

*Este capítulo profundiza en las decisiones de ingeniería que sostienen la aplicación: cómo está organizada, cómo se mantiene el sorteo como una pieza independiente, cómo se resuelven la autorización y el aislamiento entre cooperativas, y cómo los datos cruzan del backend al frontend. A diferencia de los Capítulos @cap-intro y @cap-funcional —suficientes para comprender qué es y qué hace MTAV—, este capítulo está dirigido a quien quiera entender cómo está construido. El detalle de referencia (esquemas completos, glosarios, diagramas) se remite a los apéndices.*


## Arquitectura de la aplicación {#sec-arquitectura}

MTAV en línea es un **monolito full-stack**: un único proyecto que contiene el backend (Laravel) y el frontend (Vue), unidos por Inertia.js, sin una API REST o GraphQL intermedia. La justificación de esta elección —y sus compromisos— se discutió en la Sección @sec-stack; aquí interesa su consecuencia arquitectónica: los controladores de Laravel resuelven cada petición devolviendo directamente el componente Vue de la página con sus datos, y el servidor es la única fuente de verdad.

Internamente, el backend se organiza en **capas** con responsabilidades separadas: la capa HTTP (controladores, *form requests* de validación y políticas de autorización), una capa de **servicios** que concentra la lógica de dominio (por ejemplo, `LotteryService` para el sorteo), y los modelos Eloquent que median el acceso a la base de datos. Los controladores quedan delgados: reciben la petición, delegan en un servicio y devuelven una respuesta.

Un principio transversal es el **desacople por eventos**. Las acciones del dominio emiten eventos de Laravel, y *listeners* independientes se ocupan de los efectos secundarios —enviar notificaciones, difundir actualizaciones en tiempo real por WebSocket (Reverb)—. Por ejemplo, la ejecución del sorteo emite eventos (`GroupLotteryExecuted`, `ProjectLotteryExecuted`) que disparan, por separado, la creación de notificaciones y el *broadcast* a los clientes conectados. Así, la lógica central no conoce ni depende de esos efectos: agregar o modificar una notificación no toca el código del sorteo.

Todo esto corre sobre el entorno **Docker** multiservicio introducido en la Sección @sec-stack —PHP, servidor web, base de datos, servidor de tiempo real y compilación de recursos, cada uno en su contenedor—. Ese entorno resuelve la paridad entre desarrollo y producción, pero plantea a la vez un problema de **experiencia de desarrollo (DX)**: coordinar varios servicios, separar los distintos entornos (desarrollo, pruebas, producción) y encadenar sin equivocarse las operaciones habituales —levantar todo, migrar, sembrar datos, correr las pruebas— es complejo y frágil. Dejar esa complejidad expuesta sería una fuente permanente de fricción y de errores para quien retome el proyecto.

Por eso la infraestructura se trató como parte del producto, y no como un accesorio. Los servicios se definen de forma genérica y los **entornos** se arman componiéndolos —desarrollo con recarga en vivo, pruebas aisladas en memoria, ejecución extremo a extremo, y una composición de producción con imágenes autocontenidas—, cada uno aislado y capaz de convivir con los demás. Sobre esa base, un único comando, `./mtav`, unifica toda la operación: desde un clon limpio, `mtav up` crea la configuración, construye las imágenes, instala dependencias, migra y siembra la base, y deja la aplicación funcionando —un solo comando en lugar de la larga secuencia de pasos manuales que exigiría hacerlo a mano—. El objetivo es explícito: que quien tome el proyecto pueda empezar a trabajar en minutos y operar sin riesgo, sin necesidad de dominar los detalles de Docker. Esta capa de herramientas y entornos se detalla en el Apéndice I.


## El sorteo como capa independiente {#sec-capa-sorteo}

El módulo del sorteo está diseñado como una **capa casi portátil**, deliberadamente desacoplada del resto de la aplicación. Dos decisiones lo hacen posible.

Primero, el sorteo se resuelve detrás de una interfaz de **estrategia** (`SolverInterface`): los distintos *solvers* —el basado en GLPK para producción, y otros auxiliares (aleatorio y de prueba)— son intercambiables y se seleccionan por configuración. La lógica que orquesta el sorteo depende de la interfaz, no de un *solver* concreto. Esto es *plug-and-play*: se puede sustituir el algoritmo sin tocar el resto del sistema. La ventaja es triple —**testeabilidad** (se usan *solvers* de prueba deterministas en los tests), **extensibilidad** (incorporar un nuevo enfoque, como la programación por restricciones propuesta por Fierro, es agregar un *solver*) y **claridad** (el algoritmo queda aislado)—.

Segundo, el orquestador del sorteo opera sobre **datos crudos** (listas de identificadores), no sobre modelos Eloquent. El acoplamiento con el resto de la aplicación es mínimo: la lógica de asignación recibe familias, unidades y preferencias como estructuras simples y devuelve asignaciones, sin conocer la base de datos ni el framework. Esta independencia es lo que permitió, entre otras cosas, medir el algoritmo de forma aislada (§@sec-resultados).


## Autorización y alcance de proyecto {#sec-autorizacion}

MTAV atiende a muchas cooperativas desde una misma instalación, de modo que dos garantías son críticas: que cada usuario solo pueda hacer lo que su rol permite, y que los datos de una cooperativa nunca se filtren a otra. Ambas se aplican en **varias capas, de forma redundante y deliberada** (defensa en profundidad).

**Autorización por políticas.** Cada acción sobre cada recurso se autoriza a través de una clase *Policy*. Por ejemplo, en la política de familias, crear y eliminar exige ser administrador, mientras que modificar lo puede hacer un administrador o un cooperativista sobre su propia familia. Sobre estas políticas hay un atajo: `Gate::before` concede todo a los superadministradores, evitando repetir esa excepción en cada política.

**Alcance de proyecto.** Casi todos los modelos llevan un *global scope* (`ProjectScope`) que filtra automáticamente las consultas al proyecto activo, resuelto en cada petición. La consecuencia es que una consulta **no puede** devolver datos de otra cooperativa aunque el código lo pidiera: el aislamiento se garantiza a nivel de la consulta, no en los controladores, donde sería fácil olvidarlo.

**Roles en capas.** El sistema de tres roles se aplica simultáneamente en el *scope* de consulta, en la política y en el controlador. Esta redundancia es intencional: si una capa falla o se omite por error, otra sigue protegiendo.

**La clave `can`.** Cada recurso serializado hacia el frontend incluye, para el usuario actual, qué acciones puede realizar sobre ese objeto (por ejemplo, si puede editarlo o eliminarlo). Así, la interfaz sabe qué mostrar o habilitar sin necesidad de consultas adicionales, y la decisión de permisos vive en un solo lugar —las políticas (Laravel Policies) del backend—.


## Manejo de modelos y recursos {#sec-recursos}

Un punto de fricción habitual en las aplicaciones full-stack es cómo viajan los datos del backend al frontend: normalmente hay que escribir, para cada modelo, código de serialización que decida qué campos exponer y con qué forma. En MTAV ese trabajo está automatizado.

Los modelos se convierten a una estructura JSON consistente de forma automática: devolver un modelo desde un controlador produce un recurso bien formado, sin escribir serialización a mano para cada uno. El mismo mecanismo admite **subconjuntos** de recurso —distintos niveles de detalle según el contexto— y **embebe los permisos** del usuario sobre cada objeto (la clave `can` descrita en §@sec-autorizacion). El resultado es que los datos cruzan del backend al frontend de forma transparente: la misma forma de modelo, con sus permisos, llega a los componentes Vue a través de Inertia.

En la dirección opuesta —del frontend al backend— ocurre algo análogo con los **formularios**. En lugar de definir a mano, para cada modelo, un esquema de formulario en el cliente, la especificación de cada formulario se **genera a partir de las reglas de validación** del backend (los *form requests* de la Sección @sec-arquitectura): campos, tipos de control y restricciones se derivan de las mismas reglas que validarán el envío. Las reglas de validación son así la única fuente de verdad: un cambio en ellas se refleja a la vez en el formulario que ve el usuario y en la validación que lo protege.

Estas dos capacidades —la generación de formularios a partir de las reglas de validación y la serialización automática de recursos— se extrajeron a dos pequeños paquetes reutilizables que pueden resultar útiles en otros proyectos, como aporte a la comunidad de código abierto. Su superficie completa se documenta en el Apéndice A (glosario de recursos JSON).
