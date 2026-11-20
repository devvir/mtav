```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Introducción y contexto {#cap-intro}

::: nuevo
*Este capítulo presenta el problema y la propuesta del trabajo: el cooperativismo de vivienda en Uruguay y por qué asignar las viviendas al terminar una obra es un problema difícil (Sección @sec-cooperativas); el método de asignación desarrollado en la Facultad de Ingeniería —MTAV—, su validación y la limitación operativa que lo restringe (Sección @sec-antecedentes); la motivación para llevarlo a una plataforma de autogestión (Sección @sec-motivacion); y una visión general de esa plataforma, MTAV en línea (Sección @sec-vision). Está dirigido a cualquier lector y no requiere formación matemática ni de programación.* [#28: párrafo inicial del capítulo, como en los demás]{.rev #rev-28}
:::


## Las cooperativas de vivienda en Uruguay {#sec-cooperativas}

Esta sección presenta el contexto del problema: la historia y relevancia del cooperativismo de vivienda en Uruguay (§@sec-historia), cómo se asignan hoy las viviendas al finalizar una obra (§@sec-proceso-asignacion) y por qué esa asignación es un problema difícil (§@sec-problema).

### Historia y relevancia social {#sec-historia}

Uruguay tiene una de las tradiciones de vivienda cooperativa más desarrolladas del mundo. Desde hace más de cincuenta años, las cooperativas de vivienda son un pilar de la política habitacional del país: ofrecen a familias de sectores medios y trabajadores una vía de acceso a la vivienda digna que no depende del mercado inmobiliario privado ni de la adjudicación directa por parte del Estado.

El marco que hizo posible este modelo es la **Ley N.º 13.728 de 1968** (Plan Nacional de Vivienda), que creó el sistema de financiamiento público de vivienda social y reconoció explícitamente a las cooperativas como vehículo legítimo para acceder a él. El régimen cooperativo está regulado hoy por la **Ley General de Cooperativas N.º 18.407 de 2008**, que sustituyó el capítulo cooperativo de aquella ley fundacional. Esa ley distingue dos grandes modalidades (art. 128): las **cooperativas de usuarios**, que "sólo atribuyen a los socios el derecho de *uso y goce* sobre las viviendas sin limitación de tiempo" (art. 129), y las **cooperativas de propietarios**, en las que los socios acceden a la propiedad individual de su unidad (art. 130). El derecho de uso y goce es inherente a la calidad de socio: no puede cederse en el mercado (art. 54), pero sí se transmite a los herederos, que pueden optar por continuar en el uso y goce de la vivienda (art. 141). En la práctica uruguaya predomina ampliamente el modelo de usuarios: el socio nunca es dueño individual de su vivienda, sino titular de un derecho de uso dentro de un patrimonio colectivo (Ley N.º 13.728, 1968; Ley N.º 18.407, 2008).

En **1970** se fundó **FUCVAM** (Federación Uruguaya de Cooperativas de Vivienda por Ayuda Mutua), que agrupa a las cooperativas construidas mediante el aporte de trabajo de los propios socios. FUCVAM se convirtió en un actor central del movimiento y es hoy un referente internacional del cooperativismo habitacional; nuclea a varios cientos de cooperativas que representan a decenas de miles de familias en todo el país (FUCVAM, s.f.).[^fucvam] La otra gran federación es **FECOVI** (Federación de Cooperativas de Vivienda de Usuarios por Ahorro Previo), fundada en 1969, que agrupa a las cooperativas de la modalidad de ahorro previo —alrededor de 120 cooperativas que representan a más de 5 000 familias (FECOVI, s.f.)— y a la que pertenece la mayoría de las cooperativas que han utilizado MTAV. Existen además federaciones menores, y cooperativas que no integran ninguna federación.

El modelo cooperativo se basa en la **autogestión**: un grupo de familias se organiza, accede a financiamiento —típicamente a través del Estado y la banca pública—, ejecuta o contrata la construcción, y al finalizar habita y administra colectivamente el conjunto. A lo largo de cinco décadas este modelo ha demostrado ser una herramienta eficaz de acceso a la vivienda para amplios sectores de la población. También ha generado desafíos organizativos propios, entre ellos el problema que motiva este trabajo: **cómo asignar las unidades construidas entre las familias socias** al finalizar la obra.

### El proceso de asignación de viviendas {#sec-proceso-asignacion}

Al finalizar la construcción de un proyecto cooperativo, el conjunto de unidades queda listo para ser habitado y surge una pregunta que parece simple pero es delicada: **¿qué familia vive en qué unidad?**

Las viviendas de un mismo proyecto no son idénticas. Difieren en tamaño, orientación, piso, ubicación dentro del conjunto, proximidad a espacios comunes o a la calle, y cantidad de dormitorios. Cada familia tiene necesidades y preferencias distintas: una familia con adultos mayores puede necesitar una planta baja; otra con niños pequeños puede preferir alejarse del tráfico; una pareja joven puede valorar la vista por sobre la comodidad de acceso. Inevitablemente, las preferencias entran en conflicto: varias familias querrán la misma unidad.

En el modelo uruguayo, **cada colectivo decide de forma autónoma cómo distribuir las unidades**. No hay un método único impuesto por ley: es una decisión de cada cooperativa. El mecanismo más habitual es el **sorteo aleatorio**, realizado de forma pública y presenciada por los socios. Algunas cooperativas incorporan además criterios de preselección —por ejemplo, favorecer a familias con adultos mayores o personas con discapacidad para las unidades más accesibles— antes o en lugar del sorteo puro.

El sorteo aleatorio tiene una virtud evidente: es simple y difícilmente cuestionable en su procedimiento. Pero tiene una desventaja igual de evidente: **ignora por completo las preferencias de las familias**. El resultado es justo en un sentido estadístico —todos tuvieron la misma probabilidad—, pero no en el sentido de la satisfacción: nada impide que muchas familias terminen en la unidad que menos querían.

Desde 2016, la Facultad de Ingeniería de la Universidad de la República desarrolló un método alternativo basado en optimización matemática[ (Prino, Sánchez y Cancela, 2016)]{.nuevo}, que fue aplicado con éxito en varios proyectos reales[ (Fierro, 2024)]{.nuevo}. [#17: citas agregadas]{.rev #rev-17} Ese método —su fundamento, su validación y su limitación operativa— es el antecedente directo de este trabajo y se describe en la Sección @sec-antecedentes.

### El problema de la asignación: dificultad y consecuencias {#sec-problema}

El desafío es diseñar un proceso de asignación que sea, simultáneamente:

- **Justo**: ninguna familia debería recibir un resultado desproporcionadamente peor que las demás.
- **Eficiente**: dentro de lo justo, el resultado debería maximizar la satisfacción del conjunto.
- **Transparente**: el proceso debe poder explicarse y auditarse; cualquier socio debe poder verificar que el resultado fue correcto.
- **No negociable**: una vez ejecutado, el resultado no puede modificarse ni cuestionarse con base en argumentos subjetivos.

Estos criterios están en tensión. El **sorteo aleatorio** cumple con transparencia y no-negociabilidad, pero falla en justicia y eficiencia. Una **asignación negociada** en asamblea puede ser eficiente, pero rara vez es transparente o resistente a cuestionamientos, y puede tensionar la cohesión de un grupo que muchas veces lleva años construyendo no solo viviendas sino también una comunidad.

Lo que se necesita es un método que **optimice la satisfacción colectiva respetando la equidad, y que además sea verificable**. Ese es el problema que resuelve **MTAV**, el sistema de asignación desarrollado en la Facultad de Ingeniería que se describe en la Sección @sec-antecedentes; este trabajo toma ese sistema ya probado y lo lleva a una plataforma de autogestión al alcance de cualquier cooperativa.


## Antecedentes: el algoritmo de asignación y el MTAV original {#sec-antecedentes}

Esta sección describe el antecedente directo de este trabajo: la herramienta MTAV desarrollada en la Facultad de Ingeniería (§@sec-mtav-original), su validación en proyectos reales (§@sec-validacion) y la limitación operativa que motiva esta tesis (§@sec-cuello-operativo).

### El MTAV original: la herramienta de la Facultad de Ingeniería {#sec-mtav-original}

Frente a las limitaciones del sorteo aleatorio y de la asignación negociada, en la Facultad de Ingeniería de la Universidad de la República se desarrolló una alternativa basada en optimización matemática: **MTAV** (*Mejor Tecnología de Asignación de Viviendas*).

MTAV fue desarrollado por un equipo de docentes, estudiantes y egresados de la Facultad, con participación de cooperativistas, y evolucionó a lo largo de varios trabajos sucesivos (Fagián, Prino y Sánchez, 2017; Fierro, 2020; Cancela, Fierro, Manera y Prino, 2022) a partir de su formulación inicial en 2016 (Prino, Sánchez y Cancela, 2016); el programa se distribuye como software libre (Sánchez y Prino, s.f.). La idea central es simple de enunciar: cada familia ordena según su preferencia las viviendas disponibles, y el sistema busca —mediante **programación lineal entera**, resuelta con el *solver* de código abierto GLPK[^glpk]— la asignación que sea a la vez justa y globalmente satisfactoria.

Para lograrlo, MTAV persigue **dos objetivos en orden de prioridad estricta**: primero la equidad y, solo después —sin sacrificar nada de esa equidad—, la satisfacción global. En la práctica, esto se resuelve en dos etapas encadenadas:

1. **Primero, la equidad.** La primera etapa minimiza la *peor* posición en el ranking (el peor **rango**) que recibe cualquier familia; en otras palabras, busca que la familia menos favorecida quede lo mejor posible. Este es el criterio de **equidad max-min**: garantiza que ninguna familia quede desproporcionadamente perjudicada respecto de las demás.
2. **Después, la satisfacción global.** Fijada esa cota de equidad, la segunda etapa elige, entre todas las asignaciones que la respetan, aquella que maximiza la satisfacción del conjunto, minimizando la suma de las posiciones asignadas.

El resultado tiene propiedades valiosas: es **óptimo** (no existe otra asignación mejor bajo estos criterios), **verificable** (puede auditarse mostrando las preferencias y el resultado) y **equitativo ante iguales**[: cuando dos familias tienen las mismas preferencias, el método original añade un desempate aleatorio que les da la misma probabilidad de obtener la vivienda en disputa]{.cambio}. Estas propiedades no son solo intuitivas: fueron analizadas formalmente desde la teoría del diseño de mercados, que confirma que MTAV es **eficiente en el sentido de Pareto** —no existe otra asignación que mejore a una familia sin perjudicar a otra— y que **trata por igual a quienes son iguales** (Paleo Arrarte, 2021). *(La formulación matemática completa se detalla en el Capítulo @cap-sorteo y en el Apéndice C.)*

### Validación en el mundo real {#sec-validacion}

MTAV no es un resultado puramente teórico. Fue aplicado en numerosos proyectos cooperativos reales en Uruguay —más de veinte— y su uso fue muy bien valorado por las cooperativas que lo eligieron.[^mtav-uso] Al igual que el sorteo aleatorio, MTAV es **una opción entre otras**: cada colectivo decide libremente cómo asignar sus viviendas, y quienes usaron MTAV lo hicieron por elección propia.

Esta trayectoria es significativa. Indica que el enfoque matemático no solo es correcto en teoría, sino que funciona en la práctica, con familias reales, en el contexto social y legal uruguayo. El trabajo que presenta esta tesis **hereda esa validación**: el algoritmo de fondo es el mismo (con una mejora en la primera etapa, descrita en el Capítulo @cap-sorteo), y lo que se construye alrededor —la plataforma— es nuevo.

### El cuello de botella operativo {#sec-cuello-operativo}

A pesar de su solidez matemática y su eficacia probada, el MTAV original tiene una limitación importante que no es algorítmica sino **operativa**: no es una herramienta pensada para que cualquier persona la use por su cuenta. Esa limitación, y no el algoritmo, es lo que este trabajo se propone resolver.

En su versión inicial, la herramienta era un programa de línea de comandos[^mtav-repo]: usarla implicaba instalar Python y el *solver* GLPK en la máquina, preparar las preferencias en una planilla CSV con un formato preciso —una matriz de familias por unidades, cargada a mano—, ejecutar el programa desde la consola y leer el resultado en un archivo de texto.

A la fecha, MTAV se distribuye como una **aplicación de escritorio** de instalación autónoma, con interfaz gráfica y su manual de usuario (MTAV: Manual del usuario, s.f.). La operativa es considerablemente más amigable que la inicial: la matriz de preferencias se crea y edita en pantalla —o se importa desde un archivo CSV generado con una planilla electrónica—, un botón "Asignar" ejecuta la optimización, y el resultado se muestra en pantalla y puede exportarse a PDF. Aun así, el flujo de un sorteo real sigue siendo esencialmente **manual y centralizado**: la recolección de preferencias típica consiste en distribuir planillas electrónicas a las familias, recibirlas (por correo electrónico u otro medio) y consolidarlas a mano en la matriz única que se le entrega al programa; esa matriz exige un formato estricto (ranking completo de 1 a N, sin blancos ni repetidos, con un tratamiento especial para las familias que no entregaron sus preferencias); y todo el proceso ocurre en una sola computadora, operada por una sola persona, que instala el programa, prepara los datos, ejecuta y comunica los resultados. En suma, poner en marcha un sorteo requiere [la participación de personas con formación profesional o técnica en informática, o al menos con habilidades suficientes]{.cambio} —en la práctica, muchas veces la **asistencia del equipo de MTAV**—, y el ingreso de las preferencias no es privado: pasa por planillas que terceros manipulan y consolidan.

De ese modelo de uso se desprenden varias consecuencias:

- **Dependencia técnica**: la cooperativa no puede actuar de forma completamente autónoma; depende de la disponibilidad del equipo que conoce la herramienta.
- **Fricción operativa**: la instalación y la carga manual de datos son lentas y propensas a errores.
- **Alcance de dispositivos limitado**: al ser una aplicación de escritorio, queda fuera el dispositivo más usado hoy, el teléfono móvil.
- **Confianza**: cuando quien opera la herramienta es uno de los propios socios participantes del sorteo, puede surgir desconfianza sobre el proceso, sobre todo porque el ingreso de preferencias no es privado.[^strategyproof]

Este cuello de botella es lo que se propone resolver **MTAV en línea**, la versión web y móvil que presenta este trabajo; el nombre la distingue, cuando hace falta, del MTAV original de escritorio.


## Motivación para MTAV en línea {#sec-motivacion}

Esta sección plantea la propuesta del trabajo: convertir la herramienta especializada en una plataforma de autogestión (§@sec-autogestion) y las implicancias que esa transformación tiene más allá de la comodidad operativa (§@sec-implicancias).

### De herramienta especializada a plataforma de autogestión {#sec-autogestion}

La motivación central de este trabajo es directa: si el algoritmo funciona y produce resultados justos, ¿por qué limitar su uso a quienes cuentan con acompañamiento técnico?

Esta dirección no es nueva ni improvisada. El propio trabajo académico previo sobre MTAV ya la había anticipado: en su proyecto de grado, Marcos Fierro propuso explícitamente un **"MTAV Online"** como línea de trabajo futuro, señalando las ventajas de llevar la herramienta a la web: acceso desde el navegador sin instalación, uso desde dispositivos móviles, generación de plantillas de preferencias, ingreso privado de las preferencias de cada usuario y mayor facilidad de difusión y mantenimiento (Fierro, 2024, §4.2). Esta tesis es, en buena medida, la **realización concreta de esa propuesta**.

MTAV en línea convierte aquella herramienta probada en una herramienta de autogestión. La idea es que cualquier cooperativa pueda, por sí misma:

- Registrar su proyecto, sus tipos de vivienda y sus unidades.
- Invitar a sus familias y cooperativistas, quienes ingresan sus propias preferencias directamente y en privado.
- Ejecutar el sorteo sin intermediarios.
- Ver y compartir el resultado de forma transparente.

El rol del equipo técnico deja de ser el de un operador necesario en cada sorteo y pasa a ser, en todo caso, [el de quien aprueba el proyecto cuando crece y acompaña a quien lo pide]{.cambio}.

### Implicancias más amplias {#sec-implicancias}

Eliminar la dependencia del intermediario técnico tiene [las siguientes]{.nuevo} consecuencias, que van más allá de la comodidad[.]{.quitado}[:]{.nuevo} [#30: sugerencia de edición del tutor, incorporada]{.rev #rev-30}

**Auditabilidad.** Cuando los cooperativistas ingresan sus propias preferencias, existe un registro digital de cada preferencia y de la ejecución del sorteo, con marca de tiempo. Cualquier socio puede ver cómo se llegó al resultado, algo cualitativamente distinto de un proceso manual cuya trazabilidad depende de la prolijidad del operador.

**Independencia.** Una cooperativa que usa la plataforma no depende de la disponibilidad de un equipo externo para ejecutar su asignación: puede hacerlo cuando lo necesite.

**Equidad de acceso.** El algoritmo ya es justo en su resultado; la plataforma agrega justicia en el *acceso*, poniéndolo al alcance de cualquier cooperativa sin mediar contacto con la Facultad.

**Privacidad y confianza.** Al ingresar cada usuario sus preferencias de forma privada, con su propia cuenta, nadie ve las preferencias ajenas antes del sorteo. Esto no solo da tranquilidad sobre el manejo de datos personales: mitiga la única vulnerabilidad teórica identificada en el análisis del algoritmo, la posibilidad de que alguien con información completa de las preferencias de los demás manipule las propias para beneficiarse (Paleo Arrarte, 2021).

**Escalabilidad.** Una plataforma web puede servir a muchas cooperativas simultáneamente sin que el costo operativo crezca en la misma proporción.

**Comunidad y naturaleza compartida.** El MTAV original vive en una sola máquina, operada por una persona, con la información concentrada en un único lugar. Una aplicación en línea es, por naturaleza, compartida: la información y la actividad del proyecto se distribuyen a todos sus usuarios casi en tiempo real, sea quién se sumó, quién publicó algo, un administrador que agrega unidades o una familia recién incorporada.

Esto habilita capacidades que un programa de escritorio no puede ofrecer: una galería de imágenes compartida, el intercambio de documentos del proyecto[ y]{.quitado}[,]{.nuevo} la gestión de eventos con confirmación de asistencia[ y un foro y un chat internos entre cooperativistas]{.nuevo} —todo ello ya implementado—[ y, como trabajo futuro, un canal de comunicación interno entre cooperativistas (véase la Sección @sec-trabajo-futuro)]{.quitado}. [Nov. 2026: el canal interno ya existe (§@sec-foro-chat)]{.rev #rev-n-comunidad} Estas capacidades evitan, además, que la comunidad tenga que dispersarse en herramientas externas de propósito general (WhatsApp, Google Drive y similares). Así, MTAV puede convertirse en el espacio propio de cada comunidad cooperativa, y no solo en la herramienta que ejecuta el sorteo.

**Extensibilidad.** Con los datos de los proyectos digitalizados, se abren posibilidades inexistentes en el flujo manual: histórico de proyectos, estadísticas, reportes e integraciones. En esta línea, el trabajo académico previo dejó planteadas extensiones al propio algoritmo, que exceden el alcance de esta tesis y se recogen como trabajo futuro (Fierro, 2024; Sección @sec-tf-algoritmo).


## MTAV: visión general del sistema {#sec-vision}

Esta sección cierra el Capítulo @cap-intro con una mirada de conjunto: qué es MTAV en línea, quiénes lo usan y cómo se recorre un proyecto de principio a fin. No entra en cómo está construido —eso corresponde a los Capítulos @cap-sorteo y @cap-ingenieria—, sino en lo que hace y la experiencia de usuario que intenta construir.

### Qué es MTAV en línea y qué reemplaza {#sec-que-es}

MTAV en línea es una aplicación web y móvil que permite a una cooperativa gestionar por sí misma todo el proceso de asignación de viviendas, desde el navegador y sin instalar nada. Reemplaza el flujo asistido descrito en la Sección @sec-cuello-operativo: en lugar de que un equipo técnico opere una aplicación de escritorio con datos cargados a mano, los propios actores de la cooperativa —su administración y sus familias socias— interactúan directamente con la plataforma desde cualquier dispositivo con conexión a internet.

MTAV en línea no reemplaza al ser humano en las decisiones importantes: la composición de las familias, la definición de los tipos de vivienda y el momento de ejecutar el sorteo siguen siendo decisiones de las personas. Lo que automatiza es la parte mecánica del proceso: recolectar las preferencias, calcular el resultado con el algoritmo heredado de MTAV y dejar registro de todo.

### Los tres roles: superadministrador, administrador y cooperativista {#sec-roles}

[Sección reescrita (noviembre de 2026): una cuenta por persona y roles por proyecto; la superadministración es una cuenta de soporte aparte; cualquiera crea proyectos, y la prevención de abusos pasa a los proyectos limitados]{.rev #rev-n-roles}

El sistema organiza a sus usuarios en tres roles con distintos niveles de acceso y responsabilidad. La cuenta es una sola por persona, y el rol se tiene **en cada proyecto**: una misma persona puede administrar una cooperativa y ser cooperativista en otra.

El **superadministrador** es el responsable de la plataforma. Es una cuenta de soporte aparte, que no participa en ningún proyecto pero alcanza a todos: puede hacer en cualquiera lo que hace un administrador, aprueba los proyectos, invita a otros superadministradores, bloquea cuentas y ve la actividad y los errores de la plataforma. No puede hacer, en cambio, lo que es personal: ordenar preferencias por una familia, responder a un evento, leer un chat ajeno. Inicialmente este rol lo ocupa el equipo de MTAV, aunque podría extenderse a otras organizaciones vinculadas al cooperativismo de vivienda, como FECOVI, fuera del ámbito universitario.

El **administrador** es el responsable operativo de un proyecto cooperativo. Quien crea un proyecto es su primer administrador. Define los tipos de vivienda y carga las unidades, registra las familias e invita a sus integrantes, dibuja el plano, publica eventos, decide qué funciones tiene el proyecto y quién puede entrar, y es quien ejecuta el sorteo cuando el proceso está listo. Un mismo administrador puede gestionar cualquier cantidad de proyectos, un proyecto puede tener uno o más administradores, y un administrador puede a la vez integrar una familia del proyecto, como ocurre en una cooperativa real.

El **cooperativista** es un integrante de una familia socia del proyecto. Puede ver la información del proyecto, ordenar en privado las preferencias de su familia, consultar el plano para ubicar las unidades, participar del foro y del chat, recibir notificaciones sobre lo que ocurre en el proyecto e invitar a nuevos integrantes de su propia familia. Cada cooperativista ve únicamente las preferencias de su propia familia, nunca las de las demás. Donde el proyecto lo permite, una persona puede ser **participante sin familia**, y participa en todo salvo en el sorteo.

Para prevenir abusos, la plataforma no restringe quién crea proyectos, sino la capacidad de cada uno hasta que un superadministrador lo aprueba: un proyecto nuevo es **limitado**, con cupo para la mayoría de las cooperativas, y pasa a ser ilimitado a pedido de sus administradores (§@sec-configuracion).

### El ciclo de vida de un proyecto cooperativo en MTAV {#sec-ciclo-mtav}

[Sección reescrita (noviembre de 2026): quién crea el proyecto, cómo entra la gente, el foro y el chat, y cómo se ejecuta y se revela el sorteo]{.rev #rev-n-ciclo-mtav}

La forma más clara de entender MTAV en línea es seguir el ciclo de vida de un proyecto, desde su creación hasta la publicación del resultado. Lo que sigue es un recorrido breve; cada etapa se describe con detalle en el Capítulo @cap-funcional.

**Configuración inicial.** Una persona de la cooperativa crea el proyecto y pasa a ser su administrador; puede sumar a otros administradores. El administrador define los tipos de vivienda disponibles —por ejemplo, "apartamento de dos dormitorios" y "apartamento de tres dormitorios"— y carga las unidades concretas de cada tipo. También puede, si quiere, dibujar el plano del proyecto y elegir qué funciones tiene el proyecto: eventos, galería, archivos, foro y chat.

**Incorporación de familias.** El administrador registra las familias participantes, asigna a cada una un tipo de vivienda e invita por correo a sus integrantes, que a su vez invitan al resto de su propia familia. Cada familia puede tener uno o más cooperativistas, cada uno con su propia cuenta.

**Recolección de preferencias.** Los cooperativistas acceden desde el celular o cualquier navegador y ordenan, de mayor a menor preferencia, las unidades de su tipo. Pueden modificar ese orden hasta que el sorteo se ejecuta.

**Comunicación y eventos.** Durante el proceso, el administrador puede publicar eventos (reuniones, asambleas, actividades sociales) y agendar el sorteo. Cualquier integrante del proyecto puede compartir fotos y documentos, abrir temas en el foro y conversar por chat con los demás; un Asistente, dentro del mismo chat, explica la aplicación a quien lo necesite. Los cooperativistas reciben notificaciones de lo que ocurre en el proyecto.

**El sorteo.** Llegada la fecha, el administrador ejecuta el sorteo. El sistema corre el algoritmo de optimización y asigna todas las unidades a la vez, y el resultado aparece para todos en el mismo momento. El resultado es definitivo: no se edita ni se negocia. Solo en situaciones excepcionales, como un error en los datos detectado más tarde, un administrador puede invalidar la ejecución completa para volver a ejecutar el sorteo cuando corresponda.

**Resultado y transparencia.** Ejecutado el sorteo, todos los cooperativistas pueden ver qué unidad recibió cada familia y leer el acta de la ejecución. El registro de la ejecución queda de forma permanente. Cada cooperativista sigue viendo solo las preferencias de su propia familia, también después del sorteo.

### Organización del documento {#sec-organizacion}

El resto del documento se organiza en cuatro capítulos más y un conjunto de apéndices. El **Capítulo @cap-funcional** describe la aplicación en términos funcionales —el ciclo de vida de un proyecto, el sorteo desde la perspectiva del usuario, y las decisiones de accesibilidad y diseño móvil— y puede leerse, junto con este Capítulo @cap-intro, sin formación técnica específica. El **Capítulo @cap-sorteo** es la profundización algorítmica: el modelo de preferencias, la formulación matemática de las dos fases, el cuello de botella de la Fase 1 y la contribución original de este trabajo —la búsqueda binaria—, con su demostración de equivalencia y sus resultados empíricos. El **Capítulo @cap-ingenieria** documenta la ingeniería del sistema —el *stack* tecnológico y su justificación, la arquitectura, el sorteo como capa independiente, autorización y manejo de datos[, las variantes de la aplicación, el Asistente, las pruebas, la entrega continua y los registros de la plataforma]{.nuevo}—, y el **Capítulo @cap-conclusiones** cierra con las conclusiones y el trabajo futuro. Los **apéndices** son material de referencia estrictamente opcional: modelo de datos, permisos, modelos matemáticos completos y auditoría, plano, pruebas, uso de IA, análisis[,]{.nuevo} manuales de usuario[ y herramientas de desarrollo]{.nuevo}.


[^glpk]: GLPK (GNU Linear Programming Kit): https://www.gnu.org/software/glpk/
[^fucvam]: Según su página institucional, "más de 730 cooperativas están federadas a FUCVAM en Uruguay, representando a más [de] 35 000 familias" (FUCVAM, s.f.).
[^mtav-uso]: Fierro (2024) refiere "numerosas cooperativas"; la cifra "más de veinte" según indicación del tutor (H. Cancela). [Pregunta para el tutor: ¿conviene incluir aquí la lista concreta de las cooperativas que usaron MTAV? Si es así, ¿podrías pasármela?]{.rev #rev-q-proyectos}
[^strategyproof]: Paleo Arrarte (2021) muestra que MTAV no siempre es *strategy-proof*: un participante con información completa de las preferencias ajenas podría manipular las propias para beneficiarse. El ingreso privado de preferencias en la plataforma web mitiga este escenario al impedir el acceso a las preferencias de los demás.
[^mtav-repo]: Repositorio público de la versión inicial, preparada por Ezequiel Sánchez y Martín Prino (Python + GLPK, ejecución por línea de comandos): https://github.com/eze91/MTAV. La versión actual de escritorio se distribuye, junto con su manual, desde https://drive.google.com/drive/folders/0B-xfr6ANdtHXLTdpd0duV1VrQWc
