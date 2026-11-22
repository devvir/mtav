```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Descripción funcional {#cap-funcional}

*Este capítulo describe el alcance de lo construido, organizado como el ciclo de vida de un proyecto [en la plataforma]{.cambio}. Junto con el Capítulo @cap-intro, es suficiente para comprender qué es y qué hace MTAV: se centra en qué hace el sistema y por qué, no en cómo está construido por dentro —eso corresponde a los Capítulos @cap-sorteo y @cap-ingenieria—. No es un manual de usuario: las guías de la aplicación se enlazan desde el Apéndice H.*


## El ciclo de vida de un proyecto [en la plataforma]{.cambio} {#sec-ciclo}

[#19: título cambiado. "De un proyecto cooperativo" sonaba a todo el proyecto de la cooperativa; la sección recorre un proyecto dentro de la plataforma]{.rev #rev-19}

[Las funcionalidades se presentan en el orden en que aparecen naturalmente en un proyecto: primero la configuración, luego la incorporación de las familias, la carga de preferencias y la comunicación, y finalmente el sorteo, que por su centralidad se trata aparte en la Sección @sec-sorteo-funcional.]{.quitado}

::: cambio
En esta sección se describen las funcionalidades de la plataforma en el orden en que aparecen naturalmente dentro de un proyecto, como se deriva de sus propias dependencias: antes de que una familia pueda expresar sus preferencias o compartir fotos, un administrador tiene que crear el proyecto y sus viviendas (§@sec-configuracion) y dar de alta a las familias e invitar a sus integrantes (§@sec-familias); y solo cuando las familias están incorporadas y sus preferencias cargadas puede ejecutarse el sorteo. Sobre esa base se presentan el plano del proyecto (§@sec-plano), la gestión de eventos (§@sec-eventos) y de medios (§@sec-medios), y las notificaciones en tiempo real (§@sec-notificaciones)[; cierran la sección el foro, el chat y el Asistente (§@sec-foro-chat) y la ayuda integrada en la aplicación (§@sec-ayuda)]{.nuevo}. La carga de preferencias y el sorteo, por su centralidad, se tratan aparte en la Sección @sec-sorteo-funcional. [#20 y #31: el orden es el de uso de la plataforma, no el de un proceso cooperativo real; se nombran sus subsecciones. La primera frase retoma la sugerida por Héctor]{.rev #rev-20}
:::

### Configuración inicial: el proyecto y sus tipos de vivienda {#sec-configuracion}

[Sección reescrita (noviembre de 2026): los proyectos ya no los crea solo un superadministrador; proyectos limitados, funciones por proyecto y puesta a punto]{.rev #rev-n-configuracion}

Cualquier persona con una cuenta confirmada puede **crear un proyecto**, y pasa a ser su primer administrador. Un proyecto recién creado es **limitado**: su capacidad es suficiente para la mayoría de las cooperativas —200 personas, 50 familias y 50 viviendas, 150 invitaciones por día, archivos de hasta 25 MB y 500 MB en total[, y un ritmo de uso acotado por persona: tantas peticiones por minuto, menos de ellas cambios]{.nuevo}—, pero no más, de modo que nadie pueda abusar de la plataforma creando proyectos o cuentas en masa. Sus administradores pueden pedir en cualquier momento que pase a ser **ilimitado**, y un superadministrador puede aprobar el pedido. La aprobación es una declaración de confianza en quienes lo gestionan y no se revierte. Cada persona puede crear a lo sumo dos proyectos limitados a la vez. Un proyecto que crea un superadministrador nace ilimitado, y este designa por invitación a su primer administrador. Así la prevención de abusos no exige intermediario: cualquier cooperativa empieza sola, y el equipo de la plataforma interviene solo para ampliar su capacidad.

Al crear un proyecto, el administrador elige qué **funcionalidades** tiene: eventos, galería, archivos, foro y chat (y, con el chat, el Asistente). Cada función puede activarse o desactivarse después, y una función apagada se oculta sin perder lo que tenía. El sorteo, en cambio, forma parte de todo proyecto y no puede desactivarse. [Las decisiones menos frecuentes —si el proyecto aparece en el directorio y quién puede sumarse, si admite participantes sin familia— quedan aparte, plegadas como configuración avanzada, para que lo común se encuentre primero.]{.nuevo} Desde el momento de la creación, el inicio del proyecto y la página de **puesta a punto** muestran, en orden, lo que falta para poder realizar el sorteo.

El primer paso es definir los **tipos de vivienda**. Un tipo agrupa unidades equivalentes a los efectos del sorteo —por ejemplo, "2 dormitorios" o "3 dormitorios"—; su papel no es meramente descriptivo: **particiona el problema de asignación**. Cada familia se asocia a un tipo y competirá únicamente por las unidades de ese tipo, de modo que el sorteo del proyecto es en realidad un sorteo independiente por cada tipo (la justificación de este modelo está en el Apéndice A). Dentro de cada tipo, el administrador carga las **unidades** concretas, identificadas por un código o nombre (p. ej. "Ap. 101"). Cada unidad permanece sin familia asignada hasta que se ejecuta el sorteo.

Los administradores pueden, además, descargar los listados de personas, familias y unidades del proyecto como planillas (CSV). Pueden también **eliminar el proyecto**. Si el proyecto tuvo historia —alguien más llegó a participar o fue invitado, o hubo eventos, archivos o un sorteo—, el proyecto es archivado: fuera de la vista, pero conservado para auditoría. Si nadie llegó a usarlo, se borra de inmediato.

### Incorporación de familias y cooperativistas {#sec-familias}

[Sección reescrita (noviembre de 2026): registro abierto, directorio de proyectos, invitaciones con vencimiento, familias que se arman desde adentro, participantes sin familia y datos de contacto]{.rev #rev-n-familias}

Las **familias** son el sujeto de la asignación: es la familia —y no el individuo— la que ordena las preferencias y la que recibe una unidad (Apéndice A). Cada familia tiene un tipo de vivienda. Las crea el administrador, invitando a la vez su primer miembro, o las crean los propios cooperativistas al sumarse, como se describe más abajo.

Hay dos caminos para **entrar a un proyecto**. El primero es la **invitación**: un administrador invita por correo a una persona, sea como miembro de una familia o como administrador; un integrante de una familia invita a otros usuarios como miembros de su familia. La invitación es un enlace que vence a las dos semanas y puede retirarse; quien no tiene cuenta la crea al seguir el enlace, y quien ya la tiene se suma con ella —la cuenta es una sola, para todos los proyectos en que la persona participa—. El segundo camino es el **directorio**: los administradores pueden dejar que su proyecto aparezca en *Encontrar un proyecto* (solo para proyectos aprobados), y elegir si cualquiera puede entrar al momento o si quienes piden sumarse deben esperar su respuesta. En este último caso, una persona rechazada puede volver a solicitar el ingreso pasados treinta días. Por defecto, un nuevo proyecto no se lista ni admite entradas sin invitación.

Quien entra a un proyecto sin estar todavía en su familia la busca desde adentro: pide sumarse a ella —y cualquiera de sus integrantes responde— o la crea si no existe. La familia puede aceptar o rechazar el pedido de incorporación del usuario; un rechazo se mantiene hasta que alguien de esa familia lo levanta. Una familia se conserva mientras algo la sostenga: integrantes, una invitación pendiente o la vivienda que recibió. Si queda vacía, sin miembros, la familia se archiva y vuelve cuando se invita a alguien a ella. Si el proyecto lo permite, una persona puede participar **sin familia** (el rol "Invitado"): toma parte en todo salvo en el sorteo, no invita y no ve datos de contacto. Si el proyecto no admite participantes sin familia, quien entra tiene que sumarse a una antes de poder ver o hacer cualquier otra cosa.

Los administradores mueven personas entre familias, nombran administradores y quitan a alguien del proyecto, pero no editan el nombre ni el correo de nadie, porque esos datos pertenecen a la persona. [Los administradores son pares: ninguno le quita el rol a otro ni lo saca del proyecto; solo un superadministrador puede, como último recurso —una cuenta usurpada—, incluso al último, y entonces nombra a otro. Quien es administrador renuncia a su rol desde su propia ficha: sigue en su familia, o como participante sin familia si el proyecto los admite, y si no, sale del proyecto; el último administrador no puede renunciar.]{.nuevo} [Nov. 2026: los administradores son pares; la renuncia al rol]{.rev #rev-n-pares} El **correo y el teléfono** de cada persona son privados salvo que ella decida mostrarlos a su proyecto; los administradores los ven siempre, y también el número de documento cuando se lo ingresa, por su uso legal.

### El plano del proyecto {#sec-plano}

[Sección reescrita (noviembre de 2026): el editor del plano se rehízo por completo]{.rev #rev-n-plano}

Opcionalmente, un proyecto puede incluir un **plano** espacial: una representación visual e interactiva de la disposición de las unidades y de lo que las rodea. Se dibuja sobre el contorno del terreno, en todos los **pisos** que hagan falta (subsuelos incluidos), y cada elemento es una forma de un **tipo**: viviendas, edificios, calles, áreas verdes, espacios comunes, estacionamientos, juegos, entradas y otras formas, cada uno con un aspecto propio que lo hace reconocible a primera vista (las áreas verdes se dibujan con árboles; las viviendas, con una marca de casa y un esbozo de sus ambientes).

El administrador **edita el plano directamente sobre el dibujo**, en una pantalla grande y con un puntero: agrega formas desde una paleta, las mueve, les cambia el tamaño desde sus esquinas y sus lados, las gira, las nombra y las pasa de piso. El dibujo se alinea con lo que tiene alrededor mediante guías, se **guarda solo** un momento después de cada cambio (y reintenta si el servidor no responde), y conserva su historial de deshacer. Solo un administrador dibuja a la vez: el editor retiene el plano mientras está abierto, y a los demás se les indica quién está dibujando. Todos consultan el plano —en el teléfono, a pantalla completa, con zoom y desplazamiento como en un mapa— y los cooperativistas lo usan para ubicar las unidades antes de ordenar sus preferencias: en la página del sorteo, arrastrar una unidad desde el plano hasta un lugar del ranking la coloca allí. La representación en metros reales, la exportación y la importación de planos quedan como trabajo futuro (§@sec-tf-plano). El formato de datos y la arquitectura de componentes se detallan en el Apéndice D.

### Gestión de eventos {#sec-eventos}

El administrador publica **eventos** de cualquier índole —reuniones, asambleas, actividades sociales, celebraciones—, presenciales o en línea. Cada evento tiene título, descripción, ubicación y fechas de inicio y fin (con una duración implícita de una hora si no se indica el fin), y un estado que el sistema deriva solo: próximo, en curso o finalizado. Un evento puede estar en borrador o publicado —solo los publicados son visibles para los cooperativistas—, y cuando admite confirmación de asistencia, cada cooperativista puede aceptar o rechazar la invitación y la lista de asistentes queda visible para todo el proyecto. [Un evento que se quita se archiva, y los administradores pueden verlo y restaurarlo; se elimina definitivamente solo si todavía era un borrador, o si se lo quita dentro de su primera hora sin que nadie haya confirmado asistencia.]{.nuevo} [Nov. 2026: archivo]{.rev #rev-n-eventos}

Un detalle de diseño relevante es que **el sorteo mismo se modela como un evento**, lo que unifica bajo un mismo modelo su programación, publicación y visibilidad con las del resto de los eventos. A diferencia de los demás, el evento de sorteo **se crea automáticamente junto con el proyecto**: el administrador no lo crea ni lo elimina; solo puede **agendarlo o reagendarlo** (fijar o cambiar su fecha) y editar su descripción —el título es fijo—, y solo mientras no haya una ejecución en curso ni completada. [La fecha fija el momento a partir del cual el sorteo puede ejecutarse; las familias ordenan sus preferencias hasta entonces.]{.nuevo} Su ejecución se trata en la Sección @sec-sorteo-funcional.

### Gestión de medios {#sec-medios}

[Sección reescrita (noviembre de 2026): galería y archivos, privacidad de los archivos, archivo y restauración]{.rev #rev-n-medios}

Cualquier integrante del proyecto puede compartir **medios**, en dos lugares: la **galería**, con las fotos y los videos, y los **archivos**, con los documentos y las grabaciones de audio —actas leídas en voz alta, un mensaje de una comisión—, que se reproducen en su propia fila sin abrirlos. Cada archivo queda asociado a quien lo subió y a la fecha en que se publicó; una vez publicado, el archivo en sí no puede modificarse: solo puede editarse su descripción. Si quien lo compartió lo quita dentro de la primera hora, se elimina. Pasado ese plazo, en cambio, deja de estar disponible pero se conserva fuera de la vista, y los administradores pueden restaurarlo.

Los archivos son **privados del proyecto**: solo quienes participan en él pueden abrirlos, y no hay direcciones públicas. Se aceptan imágenes, videos, audio y documentos; lo que podría resultar peligroso o imposible de abrir se rechaza al subirlo. En un proyecto limitado rigen los topes de tamaño por archivo y por proyecto de la Sección @sec-configuracion.

### Notificaciones y actualizaciones en tiempo real {#sec-notificaciones}

[Sección reescrita (noviembre de 2026): qué se notifica, en qué idioma, y la llegada a los dispositivos y por correo]{.rev #rev-n-notificaciones}

El sistema mantiene informados a los usuarios mediante **notificaciones** dentro de la aplicación. Una notificación puede dirigirse a una persona concreta (privada), a todos los integrantes de un proyecto, o a los superadministradores de la plataforma. Cada usuario ve únicamente las que le corresponden, en su propio idioma, y cada notificación está leída o no leída para cada usuario.

No todo cambio genera una notificación, sino los hechos que a alguien le importa conocer: el sorteo ejecutado, fallido o invalidado; una persona que se suma a una familia o al proyecto; las invitaciones y los pedidos de ingreso, y sus respuestas; un evento publicado; archivos, familias y unidades nuevos (los que una misma persona agrega en media hora cuentan como uno); un tema nuevo en el foro y las respuestas en los temas donde uno escribió. Los superadministradores reciben además aviso de cada proyecto nuevo y de cada pedido de aprobación, y el proyecto, de su aprobación. Los mensajes del chat no pasan por las notificaciones: llegan a los dispositivos por su cuenta (§@sec-foro-chat).

Estas notificaciones y otras actualizaciones se entregan **en tiempo real**: cuando ocurre algo relevante, los usuarios conectados lo ven sin recargar la página, y la página abierta actualiza lo que cambió en otro lado. Además llegan a los **dispositivos** aun con la aplicación cerrada —cada persona lo activa por dispositivo, desde su perfil—, y las que lo ameritan van también por **correo**: la confirmación de la cuenta, las invitaciones, los pedidos de sumarse a una familia y la incorporación a ella. Cómo se emiten y se difunden estas actualizaciones se describe en la Sección @sec-arquitectura.

### Foro, chat y Asistente {#sec-foro-chat}

[Sección nueva (noviembre de 2026)]{.rev #rev-n-foro-chat}

Un proyecto tiene dos canales de comunicación propios, el foro y el chat, y dentro del chat un Asistente automático.

El **foro** es para las conversaciones lentas y para lo que concierne a todos: cualquier integrante abre **temas** y responde en ellos; quien escribió edita sus mensajes, y los administradores fijan, cierran, renombran y quitan temas. Un tema nuevo se notifica al proyecto, y una respuesta, a quienes ya escribieron en él.

El **chat** es para las conversaciones en vivo, de a dos o en **grupos** con nombre; cada proyecto tiene además un grupo *Comunidad* con todos sus integrantes. Solo quienes están en una conversación la leen: ni los administradores del proyecto ni los superadministradores pasan esa barrera, porque la autorización es personal. Los mensajes llegan a los demás en el momento y a sus dispositivos, sin pasar por las notificaciones. Quien escribió puede editar o retirar un mensaje durante una hora, y un mensaje retirado se elimina por completo.

El **Asistente** es otra conversación del chat, propia de cada persona, en cada proyecto y fuera de ellos. Explica la aplicación y lleva a sus lugares —con botones y enlaces a lo que esa persona puede alcanzar según su rol, su familia y las funciones del proyecto—, y no hace nada más: no consulta la base de datos, no tiene acceso a internet y no recibe información sobre otras personas, de modo que no tiene nada que pueda filtrar. Lo que la persona escribe con sus palabras lo responde un modelo de lenguaje (una inteligencia artificial que genera texto), con un límite diario de consultas por persona; los saludos, los agradecimientos y las preguntas que él mismo ofrece se responden sin consultarlo. Si el servicio que lo responde falla, el Asistente lo informa y sigue ofreciendo lo que no depende de él. Puede ofrecer enviar al equipo de la plataforma un reporte en las palabras de la persona, que solo se envía si ella lo confirma. Su arquitectura y sus controles de privacidad se describen en la Sección @sec-asistente.

### Ayuda integrada {#sec-ayuda}

[Sección nueva (noviembre de 2026)]{.rev #rev-n-ayuda}

Como los usuarios no reciben capacitación, la aplicación lleva su propia ayuda integrada. Junto al título de las páginas hay un ícono **?** que dice para qué sirve cada una y ofrece *Cuéntame más*: un **recorrido guiado** que señala las partes de la página una por una. El recorrido de cada página se muestra solo la primera vez que se la abre en un dispositivo, y cualquiera puede apagar los recorridos y volver a encenderlos desde su perfil.

Cada rol tiene además una **guía ilustrada** —cooperativista, administrador, superadministrador— en cada idioma, con capturas de la aplicación real marcadas donde hay que tocar; cooperativistas y administradores tienen también un conjunto de **preguntas frecuentes**. Las guías y las preguntas frecuentes pueden leerse también sin cuenta, como documentos sueltos, para que alguien conozca la aplicación antes de registrarse; el Apéndice H reúne sus direcciones.

## El sorteo {#sec-sorteo-funcional}

El sorteo es el punto culminante de la etapa de construcción de una cooperativa y la razón por la que MTAV fue concebido originalmente: una vez cargadas las preferencias, asigna cada unidad a una familia de forma justa, óptima y verificable. Marca, además, el paso de la construcción a la convivencia. Esta sección describe el sorteo funcionalmente —cómo se usa y qué garantiza—[: el modelo de preferencias con que cada familia expresa lo que quiere (§@sec-preferencias-funcional), lo que el algoritmo garantiza (§@sec-garantias-funcional) y cómo se ejecuta, queda fijo y puede auditarse (§@sec-ejecucion-funcional)]{.nuevo}[; su fundamento matemático, los algoritmos y la mejora de rendimiento que aporta este trabajo]{.quitado}[. Su fundamento, su algoritmo y su mecánica]{.nuevo} se desarrollan en el Capítulo @cap-sorteo. [#21: vinculación con sus subsecciones y con el Capítulo @cap-sorteo]{.rev #rev-21}

### El modelo de preferencias {#sec-preferencias-funcional}

Cada familia expresa sus preferencias ordenando las unidades de su tipo, de la más deseada a la menos deseada, mediante una interfaz de arrastrar y soltar, accesible desde cualquier dispositivo[, y también desde el plano, con un puntero: una unidad arrastrada desde el dibujo hasta un lugar del ranking lo toma]{.nuevo}. Cualquier integrante de una familia puede consultar las preferencias de su familia y modificarlas[; un administrador que integra una familia las ordena como cualquier integrante, y quien participa sin familia no interviene en el sorteo]{.nuevo}. No es necesario ordenar todas las unidades manualmente: las que un cooperativista no posicione de forma explícita se incorporan al final del ranking[, en un orden fijo para esa familia]{.nuevo}, de modo que la preferencia de cada familia siempre cubre la totalidad de las unidades de su tipo. [Nov. 2026: ranking desde el plano; quién ordena; rankings parciales]{.rev #rev-n-preferencias}

Las preferencias pueden modificarse en cualquier momento hasta que el administrador ejecuta el sorteo; en ese instante quedan bloqueadas. [Cada cambio se guarda solo, un instante después de hacerlo.]{.nuevo} [Hasta el sorteo, cada]{.cambio}[Cada]{.quitado} familia ve únicamente sus propias preferencias[; cómo y cuándo se hacen visibles para todo el proyecto se describe en §@sec-ejecucion-funcional]{.nuevo}.

### Qué garantiza el algoritmo {#sec-garantias-funcional}

El algoritmo no asigna al azar ni por orden de llegada: encuentra una asignación **óptima** según dos criterios, en orden de prioridad estricta. Primero, la **equidad**: procura que la familia con el peor resultado quede lo mejor posible, evitando que alguna reciba un resultado desproporcionadamente malo respecto de las demás. Segundo, y sin ceder nada de esa equidad, la **satisfacción global**: entre todas las asignaciones igualmente equitativas, elige la que deja al conjunto lo más conforme posible; en términos intuitivos, favorece las asignaciones con muchas primeras y segundas opciones.

El resultado es **reproducible dadas las preferencias** —[solo los empates se resuelven al azar, de forma imparcial]{.quitado}[a diferencia del MTAV original, los empates los decide el orden de los datos, no un sorteo (§@sec-garantias)]{.nuevo}— y **verificable** [Nov. 2026: desempate]{.rev #rev-n-desempate} (puede auditarse mostrando las preferencias y la asignación). La definición precisa de estos criterios, su formulación matemática y sus propiedades se desarrollan en el Capítulo @cap-sorteo.

### Ejecución, inmutabilidad y auditoría {#sec-ejecucion-funcional}

[Sección reescrita (noviembre de 2026): cada ejecución es un registro con estados, se resuelve en segundo plano y se revela a todos a la vez; la invalidación pasó a los administradores; el acta del sorteo]{.rev #rev-n-ejecucion-funcional}

Llegada la fecha del sorteo, el administrador lo ejecuta. Cada ejecución es un **registro** del proyecto —en curso, completada, fallida o invalidada— que conserva quién la lanzó, cuándo y qué confirmó: si en algún tipo de vivienda las familias y las unidades no coinciden en número, el sistema advierte y pide confirmación antes de seguir. Al lanzarse, las preferencias quedan bloqueadas y el sistema **congela** la entrada: cada ranking tal como está en ese instante. Antes de permitir el lanzamiento, la página comprueba que el sorteo pueda ejecutarse —que llegó la fecha agendada, que no hay otra ejecución activa, que hay al menos dos familias y, en un proyecto limitado, que su número no supera la capacidad— y avisa de lo que falte. La resolución ocurre en segundo plano y la página muestra su avance paso a paso. Una vez resuelta, la asignación **se retiene** hasta cumplirse un tiempo mínimo que fija un superadministrador (veinte segundos por defecto), y entonces el resultado aparece para todos a la vez, con todas las unidades asignadas de una sola vez. Si la ejecución falla, se cierra como fallida, el administrador es avisado y el sorteo puede ejecutarse de nuevo.

El resultado es **definitivo**: no se edita ni se negocia. Solo en situaciones excepcionales —un error en los datos descubierto después— un administrador puede **invalidar** una ejecución completada, dando un motivo que todo el proyecto lee: quién invalidó, cuándo y por qué quedan a la vista en la página del sorteo y se notifican a todos, y el sorteo puede ejecutarse de nuevo, como una ejecución nueva con su propio registro.

Cada ejecución deja un **acta** permanente, que cualquier integrante del proyecto puede leer: cada familia con la unidad que recibió y la posición que esa unidad ocupaba en su propio ranking ("2 de 12"), la peor posición y el promedio, y una **huella**: un código calculado a partir de la entrada congelada y del resultado, que cambiaría si cualquiera de los dos cambiara, con el que cualquiera puede comprobar que el acta es la original. [Cada familia ve allí su ranking completo; nadie ve el de otra. Los administradores descargan además el registro completo de la ejecución, para guardarlo o entregarlo a un auditor.]{.quitado} [Cada familia ve allí su ranking completo. La auditabilidad manda: pasado un plazo desde que la ejecución se completa —48 horas por defecto, que fija un superadministrador—, la ejecución se **divulga**: el ranking de cada familia, tal como quedó registrado, pasa a estar a la vista de todo el proyecto, de modo que cualquiera pueda cotejar el resultado con las preferencias que lo produjeron, y el registro completo pasa a poder descargarlo cualquier integrante (los administradores, desde el primer momento). El plazo deja a los administradores invalidar una ejecución antes de que nadie conozca los rankings: una ejecución invalidada no se divulga nunca, así que repetir el sorteo no premia a quien haya querido anticiparse. El acta explica además, en palabras simples, cómo decide el sorteo, y los modelos con que se resuelve pueden leerse en cualquier momento. Nada de lo que se divulga es el correo, el teléfono ni el documento de nadie: el registro nombra personas y familias, y con eso le alcanza.]{.nuevo} [Nov. 2026: divulgación de los rankings y del registro completo pasado un plazo]{.rev #rev-n-divulgacion} Qué permite verificar ese registro, el mecanismo de ejecución y las garantías de inmutabilidad se detallan en la Sección @sec-auditoria.

## Accesibilidad {#sec-accesibilidad}

Esta sección explica por qué la accesibilidad es un requisito de primer orden en MTAV: el perfil real de los usuarios (§@sec-perfil), las medidas concretas implementadas (§@sec-decisiones-accesibilidad) y la internacionalización (§@sec-i18n).

### El perfil del usuario objetivo {#sec-perfil}

La base de usuarios de una cooperativa de vivienda es heterogénea e incluye a personas para quienes una interfaz mal diseñada es una barrera real: adultos mayores, personas con discapacidad y personas que acceden desde dispositivos de gama baja o con conectividad limitada. En una plataforma cuyo propósito es, justamente, que las familias participen directamente (Capítulo @cap-intro), la accesibilidad es un **requisito de primer orden**: si una parte de los cooperativistas no puede usar la herramienta, la autogestión que MTAV promete se quiebra. Por eso las decisiones de accesibilidad se tomaron desde el diseño y no como una capa cosmética posterior.

### Decisiones de diseño para accesibilidad {#sec-decisiones-accesibilidad}

[Sección reescrita (noviembre de 2026): la base de componentes es Mantine; tamaño de texto, paletas con contraste medido y verificación automática]{.rev #rev-n-accesibilidad}

Las medidas concretas adoptadas son:

- **Base sobre componentes accesibles.** Los elementos interactivos —menús, diálogos, campos— se construyen sobre una biblioteca de componentes que ya resuelve la navegación por teclado, el foco y el uso con lectores de pantalla (Sección @sec-stack). Sobre esa base, el sistema de diseño propio de MTAV (Sección @sec-arquitectura) define los componentes compartidos y su aspecto.
- **Tamaño de texto a elección.** Cada persona elige entre tres tamaños —el del navegador, un quinto más grande o dos quintos más grande—, y toda la interfaz se dimensiona a partir de él, de modo que el texto resulte legible sin obligar a hacer zoom. La elección se conserva en el dispositivo y se aplica antes de dibujar la página.
- **Paletas con contraste medido, y alto contraste.** Cada usuario elige entre tema claro, oscuro o el del sistema, y entre varias paletas de color; los colores de cada una se eligieron midiendo su contraste, de modo que el texto se lea, en cualquier combinación, por encima del mínimo que exigen las pautas de accesibilidad. Se incluye además un modo de **alto contraste**, útil para personas con baja visión, que en el plano reemplaza las texturas y el relieve por un dibujo plano.
- **Respeto por la reducción de movimiento.** Si el usuario configuró en su sistema operativo la preferencia de "reducir movimiento", la aplicación desactiva transiciones y animaciones, evitando efectos que pueden causar molestias.
- **Estructura semántica y áreas táctiles amplias**, pensadas para el uso con el dedo en pantallas pequeñas. Al cambiar de página, el foco pasa al título de la nueva.
- **Verificación automática.** Las pruebas de navegador (Sección @sec-pruebas) pasan cada página por un verificador automático de accesibilidad —salvo el dibujo del plano— y no admiten hallazgos serios ni críticos.

Estas decisiones toman como referencia las pautas internacionales de accesibilidad web **WCAG 2.1, nivel AA** (W3C, 2018), adoptadas como piso mínimo dado el perfil de usuario. La verificación formal de conformidad y el soporte completo de lectores de pantalla quedan como trabajo futuro (Sección @sec-trabajo-futuro).

### Internacionalización {#sec-i18n}

La aplicación está completamente internacionalizada: funciona en [español y en inglés]{.quitado}[cinco idiomas —español de Uruguay, inglés, francés, italiano y portugués de Brasil—, que cada persona elige para la aplicación y su correo (sin elección, el de su navegador)]{.nuevo}, y toda su interfaz —incluidos mensajes, formularios[, notificaciones]{.nuevo}, correos[, guías y la página de privacidad]{.nuevo}— proviene de diccionarios de traducción[, escritos en cada idioma y no traducidos palabra por palabra]{.nuevo}. [Sumar un nuevo idioma no requiere tocar el código: basta con agregar el diccionario correspondiente]{.quitado}[Sumar un nuevo idioma es trabajo de traducción —el diccionario, las guías y las preguntas frecuentes— más su registro en la lista de idiomas, no de programación; las pruebas automáticas rechazan un idioma al que le falte una frase, o una frase que ningún lugar use]{.nuevo}. Esto amplía el alcance de la herramienta más allá del contexto uruguayo. [Nov. 2026: cinco idiomas, por persona]{.rev #rev-n-i18n}

## Diseño *mobile-first* {#sec-mobile}

Esta sección describe el enfoque de diseño para dispositivos móviles —el medio principal de acceso esperado— y las decisiones que lo materializan.

### Enfoque *responsive* y decisiones de diseño {#sec-responsive}

La aplicación fue diseñada ***mobile-first***: primero para la pantalla del teléfono y luego extendida a pantallas mayores, no al revés. En la práctica, esto se apoya en un sistema de estilos cuyo punto de partida es el diseño móvil, y en un dimensionamiento **fluido**: tanto la tipografía como los espaciados escalan de forma continua con el tamaño de la ventana, en lugar de saltar entre unos pocos diseños fijos. El resultado es una interfaz que se adapta con naturalidad a cualquier tamaño de pantalla.

### Implicancias técnicas y de experiencia de usuario {#sec-implicancias-mobile}

La decisión responde al perfil de uso real: los cooperativistas acceden mayoritariamente desde el **celular**, muchas veces de gama baja. Priorizar el móvil garantiza que la experiencia más frecuente —la que usan las familias para ingresar sus preferencias, consultar el plano o confirmar asistencia a un evento— sea la mejor cuidada, y no una versión reducida de una interfaz pensada para el escritorio.

Este enfoque se refuerza con las decisiones de accesibilidad de la sección anterior: la tipografía amplia, las áreas táctiles y el diseño fluido sirven al mismo objetivo de que cualquier persona, en cualquier dispositivo, pueda participar. En pantallas grandes, los mismos diseños se expanden para aprovechar el espacio disponible, pero la referencia primaria sigue siendo el teléfono[: en él, las secciones principales del proyecto están en pestañas al pie de la pantalla, el plano se lee a pantalla completa (dibujarlo, en cambio, requiere una pantalla grande y un puntero) y la aplicación **se instala** en el dispositivo como una aplicación más, con su ícono]{.nuevo}. [Nov. 2026: instalable, lectura sin conexión]{.rev #rev-n-mobile}

::: nuevo
Para la conectividad limitada, la aplicación funciona **sin conexión** en lectura: conserva en el dispositivo cada página tal como se cargó por última vez y la muestra cuando el servidor no está al alcance; la página avisa que está sin conexión y retiene cualquier cambio en lugar de darlo por hecho. Buscar y filtrar en las listas responde al instante, también con una conexión lenta (cómo se logra se explica en la Sección @sec-arquitectura).
:::

