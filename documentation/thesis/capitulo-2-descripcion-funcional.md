```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Descripción funcional {#cap-funcional}

*Este capítulo describe el alcance de lo construido, organizado como el ciclo de vida de un proyecto [en la plataforma]{.cambio}. Junto con el Capítulo @cap-intro, es suficiente para comprender qué es y qué hace MTAV, sin requerir formación matemática ni de programación: se centra en qué hace el sistema y por qué, no en cómo está construido por dentro —eso corresponde a los Capítulos @cap-sorteo y @cap-ingenieria—. No es un manual de usuario: los manuales están en los apéndices.*


## El stack tecnológico {#sec-stack}

Esta sección presenta las tecnologías sobre las que está construida la plataforma y, sobre todo, el porqué de cada elección: primero el panorama general (§@sec-panorama) y luego cada pieza del stack con su justificación.

### Panorama general {#sec-panorama}

[Sección reescrita (noviembre de 2026): el frontend pasó de Vue a React y Mantine, y las versiones y bibliotecas son las de la reescritura; los criterios de elección (#18) se mantienen]{.rev #rev-n-stack}

MTAV en línea es una aplicación **full-stack** construida como un único proyecto: un backend en PHP/Laravel y un frontend en React, unidos por Inertia.js. Todo el entorno de desarrollo corre sobre Docker; en producción la aplicación entera se entrega como una única imagen (Sección @sec-entrega), y las actualizaciones en tiempo real se entregan por WebSockets mediante un servidor propio.

::: nuevo
La elección de cada pieza respondió a tres criterios generales. Primero, que fuera **software libre**: sin costos de licencia, de modo que MTAV pueda desplegarse y difundirse sin ataduras. Segundo, que fuera una tecnología **madura y con una comunidad amplia**: documentación abundante, soporte sostenido en el tiempo y un ecosistema de paquetes que resuelve los problemas habituales, lo que además facilita que otra persona pueda retomar el proyecto. Tercero, que fuera una herramienta que el autor ya **conocía en profundidad**, para concentrar el esfuerzo en el dominio —el sorteo, las preferencias, los roles— y no en el aprendizaje de la tecnología. A continuación se presenta cada pieza con su justificación concreta. [#18: criterios generales de elección del stack]{.rev #rev-18}
:::

**Laravel 13 (PHP 8.5).**[^laravel] Laravel es un framework maduro y de alta productividad que reduce al mínimo el trabajo repetitivo de infraestructura: trae resueltos, listos para usar, casi todos los problemas transversales que MTAV necesita —ruteo, un ORM (Eloquent) para hablar con la base de datos, autenticación, autorización basada en políticas, migraciones de esquema, validación, colas de trabajos en segundo plano y difusión de eventos en tiempo real—. Esto permite concentrar el esfuerzo en la lógica propia del dominio (el sorteo, las preferencias, los roles) en lugar de reimplementar cimientos que ya son estándar de la industria. Su principio de "convención sobre configuración" y su amplio ecosistema hacen, además, que el código resulte predecible para cualquier desarrollador que lo retome.

**React 19 y Mantine 9 (con TypeScript).**[^react] React organiza la interfaz en componentes —unidades pequeñas y cohesivas que se combinan y reutilizan— y la mantiene sincronizada con el estado de forma declarativa: se describe *qué* mostrar, no *cómo* actualizar la pantalla paso a paso. Mantine aporta la biblioteca de componentes —campos, diálogos, menús, calendarios, notificaciones— con la accesibilidad resuelta en cada uno (navegación por teclado, foco, atributos ARIA), sobre la que MTAV define sus propios componentes y sus *tokens* de diseño (Sección @sec-accesibilidad). TypeScript agrega verificación de tipos que detecta errores antes de ejecutar el código. La elección de React, y de Mantine sobre él, responde al tercer criterio: el autor ya había construido con ambos, para otro proyecto, un sistema de diseño —componentes, *tokens* y un laboratorio de interfaz— que MTAV incorpora como un paquete de diseño propio, con su paleta y su marca.

**Inertia.js 3.**[^inertia] Inertia es la pieza que une el backend y el frontend, y su elección es una de las decisiones de arquitectura centrales del proyecto. Permite una experiencia de página única (SPA) —navegación fluida, sin recargas completas de página— sin tener que construir ni mantener una API separada (REST o GraphQL) entre ambos extremos. Una API desacoplada habría duplicado esfuerzo y superficie de error: serialización explícita de cada recurso, versionado, autenticación aparte, y un ruteo y manejo de estado espejados en el cliente. Con Inertia, los controladores de Laravel devuelven directamente componentes de React con sus datos; el servidor sigue siendo la única fuente de verdad y desaparece la capa intermedia. El compromiso —un acoplamiento estrecho entre frontend y backend, poco apto si se necesitara exponer una API pública o servir a múltiples clientes distintos— es plenamente aceptable para una aplicación relativamente simple, con volúmenes de tráfico modestos.

**MariaDB 12.**[^mariadb] MariaDB es un fork de MySQL completamente de código abierto, con buen rendimiento y robustez. Su naturaleza relacional y de **esquema estricto** acompaña deliberadamente las decisiones de diseño del modelo de datos (véase el Apéndice A): claves foráneas y restricciones que hacen cumplir la integridad de la información en la propia base, y no solo en la aplicación. Sus garantías transaccionales (ACID) importan para operaciones que deben ocurrir de forma atómica, como la escritura de la asignación del sorteo. Que sea libre y sin costo de licencia es, además, coherente con el objetivo de que MTAV pueda desplegarse y difundirse sin ataduras.

**Docker.**[^docker] Todo el entorno de desarrollo —PHP, servidor web, base de datos, servidor de tiempo real y compilación de recursos— está containerizado con Docker. Esto brinda paridad entre desarrollo y producción (elimina el clásico "en mi máquina funciona"), reproducibilidad y una puesta en marcha sencilla, y aísla cada servicio del sistema anfitrión. Sobre esa base, el script `./mtav` envuelve las operaciones habituales (levantar el entorno, correr pruebas, acceder a la base, etc.) en una interfaz de comandos unificada. Para producción, la aplicación completa —el frontend compilado y el código PHP con sus dependencias— se construye como **una única imagen** sobre FrankenPHP,[^frankenphp] un servidor que reúne Caddy y PHP en un solo proceso; la misma imagen cumple cada rol (servidor web, servidor de WebSockets, trabajadores de cola) según el comando con que se la ejecuta (Sección @sec-entrega).

**Laravel Reverb.**[^reverb] Para las actualizaciones en tiempo real, MTAV usa Reverb, un servidor WebSocket propio integrado de forma nativa con el sistema de difusión de Laravel. Se optó por **autoalojarlo** en lugar de depender de un servicio externo de pago (como Pusher o Ably) por tres motivos: no introduce una dependencia externa ni un costo recurrente, mantiene los datos dentro de la propia infraestructura —relevante para la privacidad de la información de los cooperativistas— y otorga control total sobre el servicio. Es la pieza que habilita la naturaleza compartida y en tiempo real descrita en el Capítulo @cap-intro.

**Bibliotecas y herramientas de apoyo.** Alrededor de ese núcleo, el proyecto se apoya en un conjunto de piezas auxiliares, cada una con un rol puntual. Entre ellas:

- **Vite**[^vite] — empaquetado y servidor de desarrollo del frontend, con recarga en caliente.
- **Laravel Echo**[^echo] — cliente que recibe, en el navegador, los eventos en tiempo real emitidos por Reverb.
- **Ziggy**[^ziggy] — expone las rutas de Laravel al frontend, evitando duplicar las URLs.
- **dnd-kit**[^dndkit] — arrastrar y soltar accesible, con el que las familias ordenan sus preferencias.
- **Tabler Icons**[^tabler] — el juego de íconos de la interfaz.
- **Laravel Socialite**[^socialite] — inicio de sesión con Google, además del correo y contraseña (Apéndice B).
- **web-push**[^webpush] — notificaciones en los dispositivos por el estándar Web Push (§@sec-notificaciones).
- **SDK de Anthropic**[^anthropic] — la conexión del Asistente con el modelo de lenguaje que lo responde (§@sec-foro-chat).
- **`php-ffmpeg`**[^phpffmpeg] — procesamiento de medios (miniaturas, audio y video).
- **Pruebas:** Pest (backend, y la suite de navegador mediante su complemento sobre Playwright) y Vitest con Testing Library (frontend).[^testing]
- **Calidad de código:** PHPStan (vía Larastan), PHP Insights y Pint (PHP), ESLint y Prettier (frontend), ejecutados mediante hooks de Git y en la integración continua.[^calidad]

Las herramientas de pruebas y calidad se detallan en el Apéndice E; la entrega continua, en la Sección @sec-entrega.

[^laravel]: Laravel: https://laravel.com
[^react]: React: https://react.dev — Mantine: https://mantine.dev — TypeScript: https://www.typescriptlang.org
[^inertia]: Inertia.js: https://inertiajs.com
[^mariadb]: MariaDB: https://mariadb.org
[^docker]: Docker: https://www.docker.com
[^frankenphp]: FrankenPHP: https://frankenphp.dev
[^reverb]: Laravel Reverb: https://reverb.laravel.com
[^vite]: Vite: https://vite.dev
[^echo]: Laravel Echo: https://laravel.com/docs/broadcasting
[^ziggy]: Ziggy: https://github.com/tighten/ziggy
[^dndkit]: dnd-kit: https://dndkit.com
[^tabler]: Tabler Icons: https://tabler.io/icons
[^socialite]: Laravel Socialite: https://laravel.com/docs/socialite
[^webpush]: web-push (minishlink/web-push): https://github.com/web-push-libs/web-push-php
[^anthropic]: Anthropic SDK for PHP: https://github.com/anthropics/anthropic-sdk-php
[^phpffmpeg]: PHP-FFMpeg: https://github.com/PHP-FFMpeg/PHP-FFMpeg
[^testing]: Pest: https://pestphp.com — Playwright: https://playwright.dev — Vitest: https://vitest.dev — Testing Library: https://testing-library.com
[^calidad]: PHPStan: https://phpstan.org — PHP Insights: https://phpinsights.com — Laravel Pint: https://laravel.com/docs/pint — ESLint: https://eslint.org — Prettier: https://prettier.io

## El ciclo de vida de un proyecto [en la plataforma]{.cambio} {#sec-ciclo}

[#19: título cambiado. "De un proyecto cooperativo" sonaba a todo el proyecto de la cooperativa; la sección recorre un proyecto dentro de la plataforma]{.rev #rev-19}

[Las funcionalidades se presentan en el orden en que aparecen naturalmente en un proyecto: primero la configuración, luego la incorporación de las familias, la carga de preferencias y la comunicación, y finalmente el sorteo, que por su centralidad se trata aparte en la Sección @sec-sorteo-funcional.]{.quitado}

::: cambio
En esta sección se describen las funcionalidades de la plataforma en el orden en que se usan dentro de un proyecto, que es el que imponen sus propias dependencias: antes de que una familia pueda expresar sus preferencias o compartir fotos, una persona administradora tiene que crear el proyecto y sus viviendas (§@sec-configuracion) y dar de alta a las familias e invitar a sus integrantes (§@sec-familias); y solo cuando las familias están incorporadas y sus preferencias cargadas puede ejecutarse el sorteo. Sobre esa base se presentan el plano del proyecto (§@sec-plano), la gestión de eventos (§@sec-eventos) y de medios (§@sec-medios), y las notificaciones en tiempo real (§@sec-notificaciones)[; cierran la sección el foro, el chat y el Asistente (§@sec-foro-chat) y la ayuda integrada en la aplicación (§@sec-ayuda)]{.nuevo}. La carga de preferencias y el sorteo, por su centralidad, se tratan aparte en la Sección @sec-sorteo-funcional. [#20 y #31: el orden es el de uso de la plataforma, no el de un proceso cooperativo real; se nombran sus subsecciones. La primera frase retoma la sugerida por el tutor]{.rev #rev-20}
:::

### Configuración inicial: el proyecto y sus tipos de vivienda {#sec-configuracion}

[Sección reescrita (noviembre de 2026): los proyectos ya no los crea solo la superadministración; proyectos limitados, funciones por proyecto y puesta a punto]{.rev #rev-n-configuracion}

Cualquier persona con una cuenta confirmada puede **crear un proyecto**, y pasa a ser su primer administrador. Un proyecto recién creado es **limitado**: tiene lugar para la mayoría de las cooperativas —200 personas, 50 familias y 50 viviendas, 150 invitaciones por día, archivos de hasta 25 MB y 500 MB en total—, pero no más, de modo que nadie pueda abusar de la plataforma creando proyectos o cuentas en masa. Sus administradores piden en cualquier momento que pase a ser **ilimitado**, y un superadministrador lo aprueba: la aprobación es una declaración de confianza en quienes lo gestionan y no se revierte. Cada persona puede tener a lo sumo dos proyectos limitados a la vez. Un proyecto que crea un superadministrador nace ilimitado, y este designa por invitación a su primer administrador sin ocupar lugar alguno en él. Así la prevención de abusos no exige intermediario: cualquier cooperativa empieza sola, y el equipo de la plataforma interviene solo para ampliarle el lugar.

Al crear el proyecto, el administrador elige qué **funciones** tiene: eventos, galería, archivos, foro y chat (y, con el chat, el Asistente), cada una activable o desactivable después; una función apagada se oculta y conserva lo que tenía. El sorteo está siempre. Desde ese momento, el inicio del proyecto y la página de **puesta a punto** muestran, en orden, lo que falta para poder sortear.

El primer paso es definir los **tipos de vivienda**. Un tipo agrupa unidades equivalentes a los efectos del sorteo —por ejemplo, "2 dormitorios" o "3 dormitorios"—; su papel no es meramente descriptivo: **particiona el problema de asignación**. Cada familia se asocia a un tipo y competirá únicamente por las unidades de ese tipo, de modo que el sorteo del proyecto es en realidad un sorteo independiente por cada tipo (la justificación de este modelo está en el Apéndice A). Dentro de cada tipo, el administrador carga las **unidades** concretas, identificadas por un código o nombre (p. ej. "Ap. 101"). Cada unidad permanece sin familia asignada hasta que se ejecuta el sorteo.

Los administradores pueden, además, descargar las personas, las familias y las unidades del proyecto como planillas (CSV), y **eliminar el proyecto**: si tuvo actividad, queda archivado —fuera de la vista, pero conservado para auditoría—; si no, se borra de inmediato.

### Incorporación de familias y cooperativistas {#sec-familias}

[Sección reescrita (noviembre de 2026): registro abierto, directorio de proyectos, invitaciones con vencimiento, familias que se arman desde adentro, invitados y datos de contacto]{.rev #rev-n-familias}

Las **familias** son la unidad de asignación: es la familia —y no el individuo— la que ordena las preferencias y la que recibe una unidad (Apéndice A). Cada familia tiene un tipo de vivienda. Las crea el administrador, invitando a la vez a alguien a ella, o las crean los propios cooperativistas al sumarse, como se describe más abajo.

Hay dos caminos para **entrar a un proyecto**. El primero es la **invitación**: un administrador invita por correo a una persona a una familia, o como administrador; un integrante de una familia invita a otros, solo a la suya. La invitación es un enlace que vence a los catorce días y puede retirarse; quien no tiene cuenta la crea al abrirlo, y quien ya la tiene se suma con ella —la cuenta es una sola, para todos los proyectos en que la persona participa, con un rol distinto en cada uno—. El segundo camino es el **directorio**: los administradores pueden dejar que su proyecto aparezca en *Encontrar un proyecto* (solo una vez aprobado), y elegir si cualquiera entra al momento o si quienes piden sumarse esperan su respuesta. Una persona rechazada puede volver a pedirlo pasados treinta días. Por defecto un proyecto no se lista ni admite entradas sin invitación.

Quien entra a un proyecto sin estar todavía en su familia la busca desde adentro: pide sumarse a ella —y cualquiera de sus integrantes responde— o la crea si no existe. Un rechazo se mantiene, también frente a los administradores, hasta que alguien de esa familia lo levanta. Una familia existe mientras tenga a alguien —integrantes, una invitación pendiente o la vivienda que recibió—; si queda vacía se archiva, con su ranking, y vuelve cuando alguien es invitado a ella. Si el proyecto lo permite, una persona puede participar como **invitada**, sin familia: toma parte en todo salvo en el sorteo, no invita y no ve datos de contacto. Sin invitados, quien entra se suma a su familia antes que nada.

Los administradores mueven personas entre familias, nombran y destituyen administradores —nunca al último— y quitan a alguien del proyecto, pero no editan el nombre ni el correo de nadie: son de la persona. Un administrador puede, a la vez, integrar una familia, como ocurre en una cooperativa real. El **correo y el teléfono** de cada persona son privados salvo que ella decida mostrarlos a su proyecto; los administradores los ven siempre, y también el número de documento cuando se lo ingresa, por su uso legal.

Todos los usuarios comparten un mismo mecanismo de autenticación y una única tabla de cuentas; los roles son, en cada proyecto, los de la pertenencia de la persona a él (Apéndice A). La superadministración es una cuenta de soporte aparte, que no participa en ningún proyecto.

### El plano del proyecto {#sec-plano}

[Sección reescrita (noviembre de 2026): el editor del plano se rehízo por completo]{.rev #rev-n-plano}

Opcionalmente, un proyecto puede incluir un **plano** espacial: una representación visual e interactiva de la disposición de las unidades y de lo que las rodea. Se dibuja con SVG sobre el contorno del terreno, en todos los **pisos** que hagan falta (subsuelos incluidos), y cada elemento es una forma de un **tipo**: viviendas, edificios, calles, áreas verdes, espacios comunes, estacionamientos, juegos y entradas, cada uno con un aspecto propio que lo hace reconocible a primera vista (las áreas verdes se dibujan con árboles; las viviendas, con una marca de casa y un esbozo de sus ambientes).

Funcionalmente, el administrador **edita el plano en el lugar**, en una pantalla grande y con un puntero: agrega formas desde una paleta, las mueve, les cambia el tamaño desde sus esquinas y sus lados, las gira, las nombra y las pasa de piso; el dibujo se alinea con lo que tiene alrededor mediante guías, se **guarda solo** un momento después de cada cambio (y reintenta si el servidor no responde), y conserva su historial de deshacer. Dibuja un administrador a la vez: el editor retiene el plano mientras está abierto, y a los demás se les dice quién está dibujando. Todos consultan el plano —en el teléfono, a pantalla completa, con zoom y desplazamiento como en un mapa— y los cooperativistas lo usan para ubicar las unidades antes de ordenar sus preferencias: en la página del sorteo, arrastrar una unidad desde el plano hasta un lugar del ranking la coloca allí. La representación en metros reales, la exportación y la importación de planos quedan como trabajo futuro (§@sec-tf-plano). El formato de datos y la arquitectura de componentes se detallan en el Apéndice D.

### Gestión de eventos {#sec-eventos}

El administrador publica **eventos** de cualquier índole —reuniones, asambleas, actividades sociales, celebraciones—, presenciales o en línea. Cada evento tiene título, descripción, ubicación y fechas de inicio y fin (con una duración implícita de una hora si no se indica el fin), y un estado que el sistema deriva solo: próximo, en curso o finalizado. Un evento puede estar en borrador o publicado —solo los publicados son visibles para los cooperativistas—, y cuando admite confirmación de asistencia, cada cooperativista puede aceptar o rechazar la invitación y la lista de asistentes queda visible para todo el proyecto. [Un evento que se quita se archiva: los administradores lo ven y pueden restaurarlo.]{.nuevo} [Nov. 2026: archivo]{.rev #rev-n-eventos}

Un detalle de diseño relevante es que **el sorteo mismo se modela como un evento**, de tipo *lottery*, lo que unifica bajo un mismo modelo su programación, publicación y visibilidad con las del resto de los eventos. A diferencia de los demás, el evento de sorteo **se crea automáticamente junto con el proyecto**: el administrador no lo crea ni lo elimina; solo puede **agendarlo o reagendarlo** (fijar o cambiar su fecha) y editar su descripción —el título es fijo—, y solo mientras el sorteo no haya comenzado a ejecutarse. [La fecha fija el momento a partir del cual el sorteo puede ejecutarse; las familias ordenan sus preferencias hasta entonces.]{.nuevo} Su ejecución se trata en la Sección @sec-sorteo-funcional.

### Gestión de medios {#sec-medios}

[Sección reescrita (noviembre de 2026): galería y archivos, privacidad de los archivos, archivo y restauración]{.rev #rev-n-medios}

Cualquier integrante del proyecto puede compartir **medios**, en dos lugares: la **galería**, con las fotos y los videos, y los **archivos**, con los documentos y las grabaciones de audio —actas leídas en voz alta, un mensaje de una comisión—, que se reproducen en su propia fila sin abrirlos. Cada archivo queda asociado a quien lo subió y a la fecha en que se publicó; una vez publicado, el archivo en sí no puede modificarse: solo puede editarse su descripción. Quitarlo dentro de la hora de compartido lo elimina; después, lo archiva: nadie puede abrirlo, y los administradores pueden restaurarlo.

Los archivos son **privados del proyecto**: se sirven por una ruta que comprueba el acceso, nunca por una dirección pública. El tipo de cada archivo se verifica por su contenido y no por su nombre; se rechazan los formatos que pueden ejecutar código en la página (SVG) y las imágenes imposibles de abrir (más de 50 megapíxeles). En un proyecto limitado, cada archivo pesa hasta 25 MB y el proyecto guarda hasta 500 MB en total.

El procesamiento de medios —por ejemplo, la generación de miniaturas— se apoya en FFmpeg (Sección @sec-stack).

### Notificaciones y actualizaciones en tiempo real {#sec-notificaciones}

[Sección reescrita (noviembre de 2026): qué se notifica, en qué idioma, y la llegada a los dispositivos y por correo]{.rev #rev-n-notificaciones}

El sistema mantiene informados a los usuarios mediante **notificaciones** dentro de la aplicación. Una notificación puede dirigirse a una persona concreta (privada), a todos los integrantes de un proyecto, o a los superadministradores de la plataforma. Cada usuario ve únicamente las que le corresponden, en su propio idioma —la notificación se guarda una sola vez, como una clave de traducción con sus valores, y se escribe para cada lector—, y se distinguen dos estados por notificación y por usuario: leída o no leída.

Se notifica lo que importa, no todo cambio: el sorteo ejecutado, fallido o invalidado; una persona que se suma a una familia o al proyecto; las invitaciones y los pedidos de ingreso, y sus respuestas; un evento publicado; archivos, familias y unidades nuevos (los que una misma persona agrega en media hora cuentan como uno); un tema nuevo en el foro y las respuestas en los temas donde uno escribió. Los mensajes del chat nunca pasan por las notificaciones.

Estas notificaciones y otras actualizaciones se entregan **en tiempo real**: cuando ocurre algo relevante, los usuarios conectados lo ven sin recargar la página, mediante WebSockets (Reverb, Sección @sec-stack), y la página abierta recarga lo que cambió en otro lado. Además llegan a los **dispositivos** aun con la aplicación cerrada, por el estándar Web Push —cada persona lo activa por dispositivo, desde su perfil—, y las que lo ameritan van también por **correo**: la confirmación de la cuenta, las invitaciones, el lugar que alguien pasa a ocupar. La arquitectura de eventos y *listeners* que desacopla la emisión de estas actualizaciones se describe en la Sección @sec-arquitectura.

### Foro, chat y Asistente {#sec-foro-chat}

[Sección nueva (noviembre de 2026)]{.rev #rev-n-foro-chat}

Un proyecto tiene dos canales de comunicación propios, y un tercero que no es una persona.

El **foro** es para las conversaciones lentas y para lo que concierne a todos: cualquier integrante abre **temas** y responde en ellos; quien escribió edita sus mensajes, y los administradores fijan, cierran, renombran y quitan temas. Un tema nuevo se notifica al proyecto, y una respuesta, a quienes ya escribieron en él.

El **chat** es para las conversaciones en vivo, de a dos o en **grupos** con nombre; cada proyecto tiene además un grupo *Comunidad* con todos sus integrantes. Solo quienes están en una conversación la leen: ni los administradores del proyecto ni los superadministradores pasan esa barrera, porque la autorización es personal. Los mensajes llegan a los demás en el momento y a sus dispositivos, nunca a las notificaciones; quien escribió puede editar o retirar un mensaje durante una hora, y de lo retirado no queda nada.

El **Asistente** es otra conversación del chat, propia de cada persona, en cada proyecto y fuera de ellos. Explica la aplicación y lleva a sus lugares —con botones y enlaces a lo que esa persona puede alcanzar según su rol, su familia y las funciones del proyecto—, y no hace otra cosa: no consulta la base de datos, no tiene acceso a internet y no sabe nada de las demás personas, de modo que nada hay que pueda filtrar. Los saludos, los agradecimientos y las preguntas que él mismo ofrece se responden sin modelo de lenguaje; solo lo que la persona escribe con sus palabras va al modelo, con límites diarios por persona y por despliegue. Si el servicio falla repetidamente, descansa un cuarto de hora y lo dice; sin una clave configurada, no está disponible. Puede ofrecer enviar al equipo de la plataforma un reporte en las palabras de la persona, que solo se envía si ella lo confirma. Su arquitectura y sus controles de privacidad se describen en la Sección @sec-asistente.

### Ayuda integrada {#sec-ayuda}

[Sección nueva (noviembre de 2026)]{.rev #rev-n-ayuda}

Como los usuarios no reciben capacitación, la aplicación se explica a sí misma. Junto al título de cada página hay un ícono **?** que dice para qué sirve y ofrece *Cuéntame más*: un **recorrido guiado** que señala las partes de la página una por una. A las personas nuevas el recorrido de cada página se les muestra solo la primera vez, y cualquiera puede apagarlos y volver a encenderlos desde sus preferencias.

Cada rol tiene además una **guía ilustrada** —cooperativista, administrador, superadministrador— en cada idioma, con capturas de la aplicación real marcadas donde hay que tocar, y un conjunto de **preguntas frecuentes**. La guía del cooperativista es pública, en `/manual`, para que alguien pueda conocer la aplicación antes de tener cuenta; las otras viven dentro de ella. El Apéndice H remite a ellas.

## El sorteo {#sec-sorteo-funcional}

El sorteo es el punto culminante de la etapa de construcción de una cooperativa y la razón por la que MTAV fue concebido originalmente: una vez cargadas las preferencias, asigna cada unidad a una familia de forma justa, óptima y verificable. Marca, además, el paso de la construcción a la convivencia. Esta sección describe el sorteo funcionalmente —cómo se usa y qué garantiza—[: el modelo de preferencias con que cada familia expresa lo que quiere (§@sec-preferencias-funcional), lo que el algoritmo garantiza (§@sec-garantias-funcional) y cómo se ejecuta, queda fijo y puede auditarse (§@sec-ejecucion-funcional)]{.nuevo}[; su]{.quitado}[. Su]{.nuevo} fundamento matemático[ (Secciones @sec-problema-asignacion a @sec-formulacion)]{.nuevo}, los algoritmos y la mejora de rendimiento que aporta este trabajo[ (Secciones @sec-cuello-fase1 a @sec-resultados) y la mecánica de ejecución (Secciones @sec-ejecucion y @sec-auditoria)]{.nuevo} se desarrollan en el Capítulo @cap-sorteo. [#21: vinculación con sus subsecciones y con el Capítulo @cap-sorteo]{.rev #rev-21}

### El modelo de preferencias {#sec-preferencias-funcional}

Cada familia expresa sus preferencias ordenando las unidades de su tipo, de la más deseada a la menos deseada, mediante una interfaz de arrastrar y soltar, accesible desde cualquier dispositivo[, y también desde el plano: una unidad arrastrada desde el dibujo hasta un lugar del ranking lo toma]{.nuevo}. Cualquier integrante de una familia puede consultar las preferencias de su familia y modificarlas (§@sec-vision)[; un administrador que integra una familia las ordena como cualquier integrante, y un invitado no participa del sorteo]{.nuevo}. No es necesario ordenar todas las unidades manualmente: las que un cooperativista no posicione de forma explícita se incorporan al final del ordenamiento[, en un orden fijo para esa familia]{.nuevo}, de modo que la preferencia de cada familia siempre cubre la totalidad de las unidades de su tipo. [Nov. 2026: ranking desde el plano; quién ordena; rankings parciales]{.rev #rev-n-preferencias}

Las preferencias pueden modificarse en cualquier momento hasta que el administrador ejecuta el sorteo; en ese instante quedan bloqueadas. [Cada cambio se guarda al momento.]{.nuevo} Cada familia ve únicamente sus propias preferencias.

### Qué garantiza el algoritmo {#sec-garantias-funcional}

El algoritmo no asigna al azar ni por orden de llegada: encuentra una asignación **óptima** según dos criterios, en orden de prioridad estricta. Primero, la **equidad**: procura que la familia con el peor resultado quede lo mejor posible, evitando que alguna reciba un resultado desproporcionadamente malo respecto de las demás. Segundo, y sin ceder nada de esa equidad, la **satisfacción global**: entre todas las asignaciones igualmente equitativas, elige la que deja al conjunto lo más conforme posible —la mayor cantidad de primeras opciones, luego segundas, y así sucesivamente—.

El resultado es **reproducible dadas las preferencias** —[solo los empates se resuelven al azar, de forma imparcial]{.quitado}[los empates los decide el orden de los datos, no un sorteo (§@sec-garantias)]{.nuevo}— y **verificable** [Nov. 2026: desempate]{.rev #rev-n-desempate} (puede auditarse mostrando las preferencias y la asignación). La definición precisa de estos criterios, su formulación matemática y sus propiedades se desarrollan en el Capítulo @cap-sorteo.

### Ejecución, inmutabilidad y auditoría {#sec-ejecucion-funcional}

[Sección reescrita (noviembre de 2026): cada ejecución es un registro con estados, se resuelve en segundo plano y se revela a todos a la vez; la invalidación pasó a los administradores; el acta del sorteo]{.rev #rev-n-ejecucion-funcional}

Llegada la fecha del sorteo, el administrador lo ejecuta. Cada ejecución es un **registro** del proyecto —en curso, completada, fallida o invalidada— que conserva quién la lanzó, cuándo y qué confirmó: si en algún tipo de vivienda las familias y las unidades no coinciden en número, el sistema advierte y pide confirmación antes de seguir. Al lanzarse, las preferencias quedan bloqueadas y el sistema **congela** la entrada: cada ranking tal como está en ese instante. La resolución ocurre en segundo plano —el sorteo no se ejecuta si no hay un trabajador de cola disponible, y la página lo dice antes de que alguien lo intente— y, resuelta, la asignación **se retiene** hasta cumplirse un tiempo mínimo que fija la superadministración (veinte segundos por defecto): todos ven al sorteo trabajar, paso a paso, y el resultado aparece para todos a la vez, con todas las unidades escritas de una sola vez. Si la ejecución falla, se cierra como fallida, el administrador es avisado y el sorteo puede ejecutarse de nuevo.

El resultado es **definitivo**: no se edita ni se negocia. Solo en situaciones excepcionales —un error en los datos descubierto después— un administrador puede **invalidar** una ejecución completada, dando un motivo que todo el proyecto lee: quién invalidó, cuándo y por qué quedan a la vista en la página del sorteo y se notifican a todos, y el sorteo puede ejecutarse de nuevo, como una ejecución nueva con su propio registro.

Cada ejecución deja un **acta** permanente, que cualquier integrante del proyecto puede leer: cada familia con la unidad que recibió y el lugar que esa unidad ocupaba en su propio ranking ("2.ª de 12"), el peor lugar y el promedio, y una **huella** (SHA-256 de la entrada congelada y del resultado, tal como quedaron registrados) con la que cualquiera puede comprobar que el acta no cambió. Cada familia ve allí su ranking completo; nadie ve el de otra. Los administradores descargan además la traza entera en JSON, para guardarla o entregarla a un auditor. Ese registro es lo que hace del sorteo un proceso transparente y no repudiable: cualquiera puede verificar, después del hecho, que el resultado se corresponde con las preferencias ingresadas. El mecanismo de ejecución, las garantías de inmutabilidad y el formato del registro de auditoría se detallan en el Capítulo @cap-sorteo.

## Accesibilidad {#sec-accesibilidad}

Esta sección explica por qué la accesibilidad es un requisito de primer orden en MTAV: el perfil real de los usuarios (§@sec-perfil), las medidas concretas implementadas (§@sec-decisiones-accesibilidad) y la internacionalización (§@sec-i18n).

### El perfil del usuario objetivo {#sec-perfil}

La base de usuarios de una cooperativa de vivienda es heterogénea e incluye a personas para quienes una interfaz mal diseñada es una barrera real: adultos mayores, personas con discapacidad y personas que acceden desde dispositivos de gama baja o con conectividad limitada. En una plataforma cuyo propósito es, justamente, que las familias participen directamente (Capítulo @cap-intro), la accesibilidad no es un agregado posterior sino un **requisito de primer orden**: si una parte de los cooperativistas no puede usar la herramienta, la autogestión que MTAV promete se quiebra. Por eso las decisiones de accesibilidad se tomaron desde el diseño y no como una capa cosmética posterior.

### Decisiones de diseño para accesibilidad {#sec-decisiones-accesibilidad}

[Sección reescrita (noviembre de 2026): la base de componentes es Mantine; tamaño de texto, paletas con contraste medido y verificación automática]{.rev #rev-n-accesibilidad}

Las medidas concretas adoptadas son:

- **Base sobre componentes accesibles.** La interfaz se construye sobre Mantine, una biblioteca de componentes que implementa las pautas de accesibilidad en los elementos interactivos —navegación por teclado, gestión del foco y atributos ARIA en menús, diálogos y campos—. Sobre esa base, el sistema de diseño propio de MTAV (Sección @sec-arquitectura) define los componentes compartidos y los *tokens* de los que toman su aspecto: ninguna página nombra un color.
- **Tamaño de texto a elección.** Cada persona elige entre tres tamaños —el del navegador, un quinto más grande o dos quintos más grande—, y toda la interfaz se dimensiona a partir de él (todas las medidas están en `rem`), de modo que el texto resulte legible sin obligar a hacer zoom. La elección se conserva en el dispositivo y se aplica antes del primer dibujado.
- **Paletas con contraste medido, y alto contraste.** Cada usuario elige entre tema claro, oscuro o el del sistema, y entre varias paletas de color; los colores de cada una se eligieron midiendo su contraste, de modo que el texto se lea a 4,6:1 o más en cualquier combinación. Se incluye además un modo de **alto contraste**, útil para personas con baja visión, que en el plano reemplaza las texturas y el relieve por un dibujo plano.
- **Respeto por la reducción de movimiento.** Si el usuario configuró en su sistema operativo la preferencia de "reducir movimiento", la aplicación desactiva transiciones y animaciones, evitando efectos que pueden causar molestias.
- **HTML semántico y áreas táctiles amplias**, pensadas para el uso con el dedo en pantallas pequeñas. Al cambiar de página, el foco pasa al título de la nueva.
- **Verificación automática.** La suite de navegador (Apéndice E) pasa cada página por axe, un verificador de accesibilidad, y no admite hallazgos serios ni críticos.

Estas decisiones toman como referencia las pautas **WCAG AA**, adoptadas como piso mínimo dado el perfil de usuario. La verificación formal de conformidad y el soporte completo de lectores de pantalla quedan como trabajo futuro (Sección @sec-trabajo-futuro).

### Internacionalización {#sec-i18n}

La aplicación está completamente internacionalizada: funciona en [español y en inglés]{.quitado}[cinco idiomas —español, inglés, francés, italiano y portugués de Brasil—, que cada persona elige para la aplicación y su correo (sin elección, el de su navegador)]{.nuevo}, y toda su interfaz —incluidos mensajes, formularios[, notificaciones]{.nuevo}, correos[, guías y la página de privacidad]{.nuevo}— proviene de diccionarios de traducción[, escritos en cada idioma y no traducidos palabra por palabra]{.nuevo}. Sumar un nuevo idioma no requiere tocar el código: basta con agregar los diccionarios correspondientes (un archivo por idioma, más los archivos temáticos de Laravel)[; las pruebas rechazan un idioma al que le falte una frase, o una frase que ningún lugar use]{.nuevo}. Esto amplía el alcance de la herramienta más allá del contexto uruguayo. [Nov. 2026: cinco idiomas, por persona]{.rev #rev-n-i18n}

## Diseño mobile-first {#sec-mobile}

Esta sección describe el enfoque de diseño para dispositivos móviles —el medio principal de acceso esperado— y las decisiones que lo materializan.

### Enfoque responsive y decisiones de diseño {#sec-responsive}

La aplicación fue diseñada **mobile-first**: primero para la pantalla del teléfono y luego extendida a pantallas mayores, no al revés. En la práctica, esto se apoya en un sistema de estilos cuyo punto de partida es el diseño móvil, y en un dimensionamiento **fluido**: tanto la tipografía como los espaciados escalan de forma continua con el tamaño de la ventana, en lugar de saltar entre unos pocos diseños fijos. El resultado es una interfaz que se adapta con naturalidad a cualquier tamaño de pantalla.

### Implicancias técnicas y de experiencia de usuario {#sec-implicancias-mobile}

La decisión responde al perfil de uso real: los cooperativistas acceden mayoritariamente desde el **celular**, muchas veces de gama baja. Priorizar el móvil garantiza que la experiencia más frecuente —la que usan las familias para ingresar sus preferencias, consultar el plano o confirmar asistencia a un evento— sea la mejor cuidada, y no una versión reducida de una interfaz pensada para el escritorio.

Este enfoque se refuerza con las decisiones de accesibilidad de la sección anterior: la tipografía amplia, las áreas táctiles y el diseño fluido sirven al mismo objetivo de que cualquier persona, en cualquier dispositivo, pueda participar. En pantallas grandes, los mismos diseños se expanden para aprovechar el espacio disponible, pero la referencia primaria sigue siendo el teléfono[: en él, las secciones principales del proyecto están en pestañas al pie de la pantalla, el plano se lee a pantalla completa (dibujarlo, en cambio, requiere una pantalla grande y un puntero) y la aplicación **se instala** en el dispositivo como una aplicación más, con su ícono]{.nuevo}. [Nov. 2026: instalable, lectura sin conexión]{.rev #rev-n-mobile}

::: nuevo
Para la conectividad limitada, la aplicación funciona **sin conexión** en lectura: un *service worker* conserva cada página tal como se cargó por última vez y las responde cuando el servidor no está al alcance; la página avisa que está sin conexión y retiene cualquier cambio en lugar de darlo por hecho. Como los datos de un proyecto son pocos —rara vez más de un par de cientos de personas—, las listas se buscan, filtran y ordenan en el propio navegador, sin un pedido al servidor por cada tecla.
:::

