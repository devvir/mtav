```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Parte I — Introducción y Contexto

> **Documento de trabajo (borrador para revisión del tutor).**
>
> **Convención de notas — `[NOTA: ...]`.** Todo lo que aparezca entre `[NOTA: ... ]` es una
> anotación de borrador: pregunta abierta, dato a confirmar, pendiente o comentario para el tutor.
> **No forma parte del texto final** y debe eliminarse antes de la entrega. Son fáciles de encontrar
> y verificar: `grep -rn "\[NOTA:" documentation/thesis/`. Antes de exportar la versión final, esa
> búsqueda debe devolver **cero** resultados. Las citas al pie y las referencias cruzadas
> (p. ej. "véase la Sección 10") **sí** son parte del texto y se conservan.

---

## 1. Las cooperativas de vivienda en Uruguay

### 1.1 Historia y relevancia social

Uruguay tiene una de las tradiciones de vivienda cooperativa más desarrolladas del mundo. Desde hace más de cincuenta años, las cooperativas de vivienda son un pilar de la política habitacional del país: ofrecen a familias de sectores medios y trabajadores una vía de acceso a la vivienda digna que no depende del mercado inmobiliario privado ni de la adjudicación directa por parte del Estado.

El marco que hizo posible este modelo es la **Ley N.º 13.728 de 1968** (Ley Nacional de Vivienda), que creó el sistema de financiamiento público de vivienda social y reconoció explícitamente a las cooperativas como vehículo legítimo para acceder a él. La ley distingue dos grandes modalidades: las **cooperativas de usuarios**, en las que la cooperativa conserva la propiedad colectiva del inmueble y cada socio recibe un derecho de *uso y goce* sobre una unidad —perpetuo y heredable, pero no transferible en el mercado—, y las **cooperativas de propietarios**, en las que los socios acceden a la propiedad individual de su unidad. En la práctica uruguaya predomina ampliamente el modelo de usuarios: el socio nunca es dueño individual de su vivienda, sino titular de un derecho de uso dentro de un patrimonio colectivo.[^ley13728]

En **1970** se fundó **FUCVAM** (Federación Uruguaya de Cooperativas de Vivienda por Ayuda Mutua), que agrupa a las cooperativas construidas mediante el aporte de trabajo de los propios socios. FUCVAM se convirtió en un actor central del movimiento y es hoy un referente internacional del cooperativismo habitacional; nuclea a varios cientos de cooperativas que representan a decenas de miles de familias en todo el país.[^fucvam]

El modelo cooperativo se basa en la **autogestión**: un grupo de familias se organiza, accede a financiamiento —típicamente a través del Estado y la banca pública—, ejecuta o contrata la construcción, y al finalizar habita y administra colectivamente el conjunto. A lo largo de cinco décadas este modelo ha demostrado ser una herramienta eficaz de acceso a la vivienda para amplios sectores de la población. También ha generado desafíos organizativos propios —entre ellos, el problema que motiva este trabajo: **cómo asignar las unidades construidas entre las familias socias** al finalizar la obra.

### 1.2 El proceso de asignación de viviendas

Al finalizar la construcción de un proyecto cooperativo, el conjunto de unidades queda listo para ser habitado y surge una pregunta que parece simple pero es delicada: **¿qué familia vive en qué unidad?**

Las viviendas de un mismo proyecto no son idénticas. Difieren en tamaño, orientación, piso, ubicación dentro del conjunto, proximidad a espacios comunes o a la calle, y cantidad de dormitorios. Cada familia tiene necesidades y preferencias distintas: una familia con adultos mayores puede necesitar una planta baja; otra con niños pequeños puede preferir alejarse del tráfico; una pareja joven puede valorar la vista por sobre la comodidad de acceso. Inevitablemente, las preferencias entran en conflicto: varias familias querrán la misma unidad.

En el modelo uruguayo, **cada colectivo decide de forma autónoma cómo distribuir las unidades**. No hay un método único impuesto por ley: es una decisión de cada cooperativa. El mecanismo más habitual es el **sorteo aleatorio**, realizado de forma pública y presenciada por los socios. Algunas cooperativas incorporan además criterios de preselección —por ejemplo, favorecer a familias con adultos mayores o personas con discapacidad para las unidades más accesibles— antes o en lugar del sorteo puro.

El sorteo aleatorio tiene una virtud evidente: es simple y difícilmente cuestionable en su procedimiento. Pero tiene una desventaja igual de evidente: **ignora por completo las preferencias de las familias**. El resultado es justo en un sentido estadístico —todos tuvieron la misma probabilidad—, pero no en el sentido de la satisfacción: nada impide que muchas familias terminen en la unidad que menos querían.

En los últimos años, la Facultad de Ingeniería de la Universidad de la República desarrolló un método alternativo basado en optimización matemática, que fue aplicado con éxito en varios proyectos reales. Al igual que el sorteo aleatorio, **es una opción entre otras**: las cooperativas que lo utilizaron lo hicieron por elección propia. Ese método —su fundamento, su validación y su limitación operativa— es el antecedente directo de este trabajo y se describe en la Sección 2.

### 1.3 El problema de la asignación: dificultad y consecuencias

El desafío es diseñar un proceso de asignación que sea, simultáneamente:

- **Justo**: ninguna familia debería recibir un resultado desproporcionadamente peor que las demás.
- **Eficiente**: dentro de lo justo, el resultado debería maximizar la satisfacción del conjunto.
- **Transparente**: el proceso debe poder explicarse y auditarse; cualquier socio debe poder verificar que el resultado fue correcto.
- **No negociable**: una vez ejecutado, el resultado no puede modificarse ni cuestionarse con base en argumentos subjetivos.

Estos criterios están en tensión. El **sorteo aleatorio** cumple con transparencia y no-negociabilidad, pero falla en justicia y eficiencia, porque descarta las preferencias. Una **asignación negociada** en asamblea puede ser eficiente, pero rara vez es transparente o resistente a cuestionamientos, y puede tensionar la cohesión de un grupo que muchas veces lleva años construyendo no solo viviendas sino también una comunidad.

Lo que se necesita es un método que **optimice la satisfacción colectiva respetando la equidad, y que además sea verificable**. Ese es exactamente el problema que resuelve **MTAV**, el sistema de asignación desarrollado en la Facultad de Ingeniería que se describe en la Sección 2; este trabajo toma ese sistema ya probado y lo lleva a una plataforma de autogestión al alcance de cualquier cooperativa.

---

## 2. Antecedentes: el algoritmo de asignación y el MTAV original

### 2.1 El MTAV original: la herramienta de la Facultad de Ingeniería

Frente a la limitación del sorteo aleatorio —que ignora las preferencias— y de la asignación negociada —que rara vez es transparente—, en la Facultad de Ingeniería de la Universidad de la República se desarrolló una alternativa basada en optimización matemática: **MTAV** (*Mejor Tecnología de Asignación de Viviendas*).

MTAV fue desarrollado por un equipo de docentes, estudiantes y egresados de la Facultad, con participación de cooperativistas, y evolucionó a lo largo de varios trabajos sucesivos a partir de su formulación inicial en 2016.[^mtav2016][^mtav-evol] La idea central es simple de enunciar: cada familia ordena según su preferencia las viviendas disponibles, y el sistema busca —mediante **programación lineal entera**, resuelta con el solver de código abierto GLPK— la asignación que sea a la vez justa y globalmente satisfactoria.

Para lograrlo, MTAV persigue **dos objetivos en orden de prioridad estricta**: primero la equidad y, solo después —sin sacrificar nada de esa equidad—, la satisfacción global. En la práctica, esto se resuelve en dos etapas encadenadas:

1. **Primero, la equidad.** La primera etapa minimiza la *peor* prioridad que recibe cualquier familia; en otras palabras, busca que la familia menos favorecida quede lo mejor posible. Este es el criterio de **equidad max-min**: garantiza que ninguna familia quede desproporcionadamente perjudicada respecto de las demás.
2. **Después, la satisfacción global.** Fijada esa cota de equidad, la segunda etapa elige, entre todas las asignaciones que la respetan, aquella que maximiza la satisfacción del conjunto —minimizando la suma total de prioridades asignadas.

El resultado tiene propiedades valiosas: es **óptimo** (no existe otra asignación mejor bajo estos criterios), **verificable** (puede auditarse mostrando las preferencias y el resultado) y **equitativo ante iguales** —cuando dos familias tienen exactamente las mismas preferencias, un desempate aleatorio les da la misma probabilidad de obtener la vivienda en disputa. Estas propiedades no son solo intuitivas: fueron analizadas formalmente desde la teoría del diseño de mercados, que confirma que MTAV es **eficiente en el sentido de Pareto** —no existe otra asignación que mejore a una familia sin perjudicar a otra— y que **trata por igual a quienes son iguales**.[^paleo] *(La formulación matemática completa se detalla en la Parte III y en el Apéndice C.)*

### 2.2 Validación en el mundo real

MTAV no es un resultado puramente teórico. Fue aplicado en numerosos proyectos cooperativos reales en Uruguay —más de veinte— y su uso fue muy bien valorado por las cooperativas que lo eligieron.[^mtav-uso] Al igual que el sorteo aleatorio, MTAV es **una opción entre otras**: cada colectivo decide libremente cómo asignar sus viviendas, y quienes usaron MTAV lo hicieron por elección propia.

Esta trayectoria es significativa. Indica que el enfoque matemático no solo es correcto en teoría, sino que funciona en la práctica, con familias reales, en el contexto social y legal uruguayo. El trabajo que presenta esta tesis **hereda esa validación**: el algoritmo de fondo es el mismo (con una mejora en la primera etapa, descrita en la Parte III), y lo que se construye alrededor —la plataforma— es nuevo.

### 2.3 El cuello de botella operativo

A pesar de su solidez matemática y su eficacia probada, el MTAV original tiene una limitación importante que no es algorítmica sino **operativa**: no es una herramienta pensada para que cualquier persona la use por su cuenta.

A la fecha, MTAV se distribuye como una **aplicación de escritorio** de instalación autónoma, con su manual de usuario. Una de las versiones publicadas de la herramienta[^mtav-repo] da una idea concreta de lo que implica usarla: instalar Python y el solver GLPK en la máquina, preparar las preferencias en una planilla CSV con un formato preciso —una matriz de familias por unidades, cargada a mano—, ejecutar el programa desde la línea de comandos y leer el resultado en un archivo de texto. En la práctica, poner en marcha un sorteo normalmente requiere la **asistencia del equipo de MTAV**: instalar y configurar el programa, preparar los datos de familias, unidades y preferencias, ejecutar el proceso e interpretar los resultados. No es un programa amigable para un cooperativista común sin acompañamiento técnico.

De ese modelo de uso se desprenden varias consecuencias:

- **Dependencia técnica**: la cooperativa no puede actuar de forma completamente autónoma; depende de la disponibilidad del equipo que conoce la herramienta.
- **Fricción operativa**: la instalación y la carga manual de datos son lentas y propensas a errores.
- **Alcance de dispositivos limitado**: al ser una aplicación de escritorio, queda fuera el dispositivo más usado hoy —el teléfono móvil.
- **Confianza**: cuando quien opera la herramienta es uno de los propios socios participantes del sorteo, puede surgir desconfianza sobre el proceso, sobre todo porque el ingreso de preferencias no es privado.[^strategyproof]

Es precisamente este cuello de botella —no el algoritmo, sino todo lo que lo rodea— lo que se propone resolver **MTAV en línea**: así llamaremos, cuando haga falta distinguirla, a la versión web y móvil que presenta este trabajo, frente al MTAV original de escritorio.

---

## 3. Motivación para MTAV en línea

### 3.1 De herramienta especializada a plataforma de autogestión

La motivación central de este trabajo es directa: si el algoritmo funciona y produce resultados justos, ¿por qué limitar su uso a quienes cuentan con acompañamiento técnico? No hay razón algorítmica para esa limitación; es puramente operativa.

Esta dirección no es nueva ni improvisada. El propio trabajo académico previo sobre MTAV ya la había anticipado: en su proyecto de grado, Marcos Fierro propuso explícitamente un **"MTAV Online"** como línea de trabajo futuro, señalando las ventajas de llevar la herramienta a la web —acceso desde el navegador sin instalación, uso desde dispositivos móviles, generación de plantillas de preferencias, ingreso privado de las preferencias de cada usuario y mayor facilidad de difusión y mantenimiento.[^mtavonline] Esta tesis es, en buena medida, la **realización concreta de esa propuesta**.

MTAV en línea convierte aquella prueba de concepto exitosa en una herramienta de autogestión. La idea es que cualquier cooperativa pueda, por sí misma:

- Registrar su proyecto, sus tipos de vivienda y sus unidades.
- Invitar a sus familias y cooperativistas, quienes ingresan sus propias preferencias directamente y en privado.
- Ejecutar el sorteo sin intermediarios.
- Ver y compartir el resultado de forma transparente.

El rol del equipo técnico deja de ser el de un operador necesario en cada sorteo y pasa a ser, en todo caso, el de quien configura o acompaña inicialmente a una cooperativa nueva.

### 3.2 Implicancias más amplias

Eliminar la dependencia del intermediario técnico tiene consecuencias que van más allá de la comodidad.

**Auditabilidad.** Cuando los cooperativistas ingresan sus propias preferencias, existe un registro digital de cada preferencia y de la ejecución del sorteo, con marca de tiempo. Cualquier socio puede ver cómo se llegó al resultado, algo cualitativamente distinto de un proceso manual cuya trazabilidad depende de la prolijidad del operador.

**Independencia.** Una cooperativa que usa la plataforma no depende de la disponibilidad de un equipo externo para ejecutar su asignación: puede hacerlo cuando lo necesite.

**Equidad de acceso.** El algoritmo ya es justo en su resultado; la plataforma agrega justicia en el *acceso*, poniéndolo al alcance de cualquier cooperativa sin mediar contacto con la Facultad.

**Privacidad y confianza.** Al ingresar cada usuario sus preferencias de forma privada, con su propia cuenta, nadie ve las preferencias ajenas antes del sorteo. Esto no solo da tranquilidad sobre el manejo de datos personales: mitiga la única vulnerabilidad teórica identificada en el análisis del algoritmo —la posibilidad de que alguien con información completa de las preferencias de los demás manipule las propias para beneficiarse.[^strategyproof]

**Escalabilidad.** Una plataforma web puede servir a muchas cooperativas simultáneamente sin que el costo operativo crezca en la misma proporción.

**Comunidad y naturaleza compartida.** Una cooperativa no construye solo viviendas: a lo largo de años construye también una comunidad. El MTAV original vive en una sola máquina, operada por una persona, con la información concentrada en un único lugar. Una aplicación en línea es, por naturaleza, compartida: la información y la actividad del proyecto se distribuyen a todos sus usuarios casi en tiempo real —quién se sumó, quién publicó algo, un administrador que agrega unidades, una familia recién incorporada.

Esto habilita capacidades que un programa de escritorio no puede ofrecer: una galería de imágenes compartida, el intercambio de documentos del proyecto y la gestión de eventos con confirmación de asistencia —todo ello ya implementado— y, como trabajo futuro, un canal de comunicación interno entre cooperativistas (véase la Sección 10). Estas capacidades evitan, además, que la comunidad tenga que dispersarse en herramientas externas de propósito general (WhatsApp, Google Drive y similares). Así, MTAV puede convertirse en el espacio propio de cada comunidad cooperativa, y no solo en la herramienta que ejecuta el sorteo.

**Extensibilidad.** Con los datos de los proyectos digitalizados, se abren posibilidades inexistentes en el flujo manual: histórico de proyectos, estadísticas, reportes e integraciones. En esta línea, el trabajo académico previo dejó planteadas varias extensiones al propio algoritmo —un criterio adicional de equidad basado en la desviación estándar y la elección sobre un frente de Pareto, la incorporación de preferencias de *vecindad* entre familias, y las preferencias "en bloque"— que exceden el alcance de esta tesis pero constituyen un camino natural de trabajo futuro (véase la Sección 10).[^fierro-futuro]

---

## 4. MTAV: visión general del sistema

Esta sección cierra la Parte I con una mirada de conjunto: qué es MTAV en línea, quiénes lo usan y cómo se recorre un proyecto de principio a fin. No entra en cómo está construido —eso corresponde a las Partes II y III—, sino en lo que hace y la experiencia de usuario que intenta construir.

### 4.1 Qué es MTAV en línea y qué reemplaza

MTAV en línea es una aplicación web y móvil que permite a una cooperativa gestionar por sí misma todo el proceso de asignación de viviendas, desde el navegador y sin instalar nada. Reemplaza el flujo asistido descrito en la Sección 2.3: en lugar de que un equipo técnico opere una aplicación de escritorio con datos cargados a mano, los propios actores de la cooperativa —su administración y sus familias socias— interactúan directamente con la plataforma desde cualquier dispositivo con conexión a internet.

MTAV en línea no reemplaza al ser humano en las decisiones importantes: la composición de las familias, la definición de los tipos de vivienda y el momento de ejecutar el sorteo siguen siendo decisiones de las personas. Lo que automatiza es la parte mecánica del proceso: recolectar las preferencias, calcular el resultado con el algoritmo heredado de MTAV y dejar registro de todo.

### 4.2 Los tres roles: superadministrador, administrador y cooperativista

El sistema organiza a sus usuarios en tres roles con distintos niveles de acceso y responsabilidad.

El **superadministrador** es el responsable de la plataforma. Crea los proyectos y designa al administrador de cada uno. Reservar la creación de proyectos a este rol es una medida deliberada para prevenir abusos —por ejemplo, que alguien cree proyectos o cuentas de forma masiva para saturar los servidores, entre otros usos maliciosos. Inicialmente este rol lo ocupa el equipo de MTAV, aunque podría extenderse a otras organizaciones vinculadas al cooperativismo de vivienda, como FECOVI, fuera del ámbito universitario.

El **administrador** es el responsable operativo de un proyecto cooperativo. Define los tipos de vivienda y carga las unidades, registra las familias y a sus integrantes, publica eventos, y es quien ejecuta el sorteo cuando el proceso está listo. Un mismo administrador puede gestionar cualquier cantidad de proyectos, y un proyecto puede tener uno o más administradores.

El **cooperativista** es un integrante de una familia socia del proyecto. Puede ver la información del proyecto, ordenar en privado las preferencias de su familia, consultar el plano para ubicar las unidades, recibir notificaciones sobre lo que ocurre en el proyecto e invitar a nuevos integrantes de su propia familia. Cada cooperativista ve únicamente las preferencias de su propia familia, nunca las de las demás.

### 4.3 El ciclo de vida de un proyecto cooperativo en MTAV

La forma más clara de entender MTAV en línea es seguir el ciclo de vida de un proyecto, desde su creación hasta la publicación del resultado. Esta misma secuencia se recorre con detalle técnico en la Parte II.

**Configuración inicial.** El superadministrador crea el proyecto y designa a uno o más administradores. El administrador define los tipos de vivienda disponibles —por ejemplo, "apartamento de dos dormitorios" y "apartamento de tres dormitorios"— y carga las unidades concretas de cada tipo, identificadas por su código o nombre. También puede cargar el plano del proyecto, que muestra dónde está cada unidad (opcional).

**Incorporación de familias.** El administrador registra las familias participantes y asigna a cada una un tipo de vivienda (cada familia solo podrá optar por unidades de su tipo). El acceso al sistema es únicamente por invitación: los superadministradores y administradores crean las familias e invitan a sus integrantes, y cada integrante puede a su vez invitar a otros miembros de su propia familia. No existe un registro público abierto. Cada familia puede tener uno o más cooperativistas, cada uno con su propia cuenta.

**Recolección de preferencias.** Los cooperativistas acceden desde el celular o cualquier navegador y ordenan, de mayor a menor preferencia, las unidades de su tipo. Pueden modificarlas en cualquier momento hasta que el administrador ejecuta el sorteo; al iniciarse la ejecución, las preferencias quedan bloqueadas automáticamente. Si el proyecto cambia —se agrega una unidad, una familia cambia de tipo—, el sistema ajusta las preferencias automáticamente, sin intervención manual.

**Comunicación y eventos.** Durante el proceso, el administrador puede publicar eventos (reuniones, asambleas, actividades sociales) y agendar el sorteo. Además, cualquier integrante del proyecto puede compartir medios (imágenes, documentos y audio). Los cooperativistas reciben notificaciones en tiempo real.

**El sorteo.** Cuando el proceso está completo, el administrador ejecuta el sorteo. El sistema bloquea las preferencias, corre el algoritmo de optimización y asigna todas las unidades a la vez. El resultado es definitivo: no se edita ni se negocia. Solo en situaciones excepcionales —por ejemplo, si más tarde se detecta un error en los datos, como una familia que había quedado sin registrar— un superadministrador puede invalidar la ejecución completa, dejando constancia en el registro de auditoría, para volver a ejecutar el sorteo cuando corresponda.

**Resultado y transparencia.** Ejecutado el sorteo, todos los cooperativistas pueden ver qué unidad recibió cada familia; el registro de la ejecución queda de forma permanente. Cada cooperativista sigue viendo solo las preferencias de su propia familia, también después del sorteo (véase la Sección 10, Trabajo futuro).

---

[^ley13728]: Ley N.º 13.728 del 17/12/1968 (Plan Nacional de Vivienda). Texto completo: IMPO — https://www.impo.com.uy/bases/leyes/13728-1968. [NOTA: verificar artículos específicos antes de citarlos en detalle.]
[^fucvam]: FUCVAM (Federación Uruguaya de Cooperativas de Vivienda por Ayuda Mutua), nacida en 1970. Según su página institucional, "más de 730 cooperativas están federadas a FUCVAM en Uruguay, representando a más [de] 35.000 familias" (FUCVAM, *Historia*, https://www.fucvam.org.uy/quienes-somos/historia, consultado el 3 de julio de 2026).
[^mtav2016]: Prino, M., Sánchez, E. y Cancela, H. (2016). *Optimal distribution of habitational units in a cooperative: A mathematical application to optimize satisfaction.* CLEI 2016 (XLII Latin American Computing Conference), pp. 1-7. doi:10.1109/CLEI.2016.7833357. Trabajo fundacional de MTAV.
[^mtav-evol]: Ediciones sucesivas: Fagián, I., Prino, M. y Sánchez, E. (2017); Fierro, M. (2020, *MTAV 3.0*); ambos FING-Udelar, supervisados por H. Cancela.
[^paleo]: Paleo Arrarte, J. M. (2021). *La asignación de apartamentos en cooperativas de vivienda: un enfoque desde el diseño de mercados.* Tesis de Maestría, FCEA-Udelar. Concluye que MTAV es eficiente ex-post (Pareto-óptimo) y satisface *equal treatment of equals*.
[^mtav-uso]: Fierro (2024) refiere "numerosas cooperativas"; la cifra "más de veinte" según indicación del tutor (H. Cancela). [NOTA: eventual lista concreta de proyectos, a solicitar al tutor si se desea incluir.]
[^strategyproof]: Paleo (2021) muestra que MTAV no siempre es *strategy-proof*: un participante con información completa de las preferencias ajenas podría manipular las propias para beneficiarse. El ingreso privado de preferencias en la plataforma web mitiga este escenario al impedir el acceso a las preferencias de los demás.
[^mtav-repo]: Repositorio público de una versión de la herramienta original (Python + GLPK, ejecución por línea de comandos): https://github.com/eze91/MTAV. [NOTA: confirmar a qué edición de MTAV corresponde este repositorio y cómo se relaciona con las versiones de escritorio posteriores, antes de la versión final.]
[^mtavonline]: Fierro, M. (2024). *Asignación de viviendas en cooperativas: Programación por restricciones y Análisis de equidad.* Proyecto de Grado, FING-Udelar (sup. H. Cancela), §4.2, subsección "MTAV Online". Licencia CC BY 4.0.
[^fierro-futuro]: Fierro (2024): propuestas de programación por restricciones (MiniZinc), medida de equidad por desviación estándar con frente de Pareto, preferencias de vecindad y preferencias en bloque.
