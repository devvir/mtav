```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Descripción funcional {#cap-funcional}

*Este capítulo describe el alcance de lo construido, organizado como el ciclo de vida de un proyecto [en la plataforma]{.cambio}. Junto con el Capítulo @cap-intro, es suficiente para comprender qué es y qué hace MTAV, sin requerir formación matemática ni de programación: se centra en qué hace el sistema y por qué, no en cómo está construido por dentro —eso corresponde a los Capítulos @cap-sorteo y @cap-ingenieria—. No es un manual de usuario: los manuales están en los apéndices.*


## El stack tecnológico {#sec-stack}

Esta sección presenta las tecnologías sobre las que está construida la plataforma y, sobre todo, el porqué de cada elección: primero el panorama general (§@sec-panorama) y luego cada pieza del stack con su justificación.

### Panorama general {#sec-panorama}

MTAV en línea es una aplicación **full-stack** construida como un único proyecto: un backend en PHP/Laravel y un frontend en Vue, unidos por Inertia.js. Todo el entorno corre sobre Docker, y las actualizaciones en tiempo real se entregan por WebSockets mediante un servidor propio.

::: nuevo
La elección de cada pieza respondió a tres criterios generales. Primero, que fuera **software libre**: sin costos de licencia, de modo que MTAV pueda desplegarse y difundirse sin ataduras. Segundo, que fuera una tecnología **madura y con una comunidad amplia**: documentación abundante, soporte sostenido en el tiempo y un ecosistema de paquetes que resuelve los problemas habituales, lo que además facilita que otra persona pueda retomar el proyecto. Tercero, que fuera una herramienta que el autor ya **conocía en profundidad**, para concentrar el esfuerzo en el dominio —el sorteo, las preferencias, los roles— y no en el aprendizaje de la tecnología. A continuación se presenta cada pieza con su justificación concreta. [#18: criterios generales de elección del stack]{.rev #rev-18}
:::

**Laravel (PHP 8.4).**[^laravel] Laravel es un framework maduro y de alta productividad que reduce al mínimo el trabajo repetitivo de infraestructura: trae resueltos, listos para usar, casi todos los problemas transversales que MTAV necesita —ruteo, un ORM (Eloquent) para hablar con la base de datos, autenticación, autorización basada en políticas, migraciones de esquema, validación, colas de trabajos en segundo plano y difusión de eventos en tiempo real—. Esto permite concentrar el esfuerzo en la lógica propia del dominio (el sorteo, las preferencias, los roles) en lugar de reimplementar cimientos que ya son estándar de la industria. Su principio de "convención sobre configuración" y su amplio ecosistema hacen, además, que el código resulte predecible para cualquier desarrollador que lo retome.

**Vue 3 (con TypeScript).**[^vue] Vue es un framework reactivo moderno que organiza la interfaz en componentes: unidades pequeñas y cohesivas que se combinan y reutilizan para construir aplicaciones arbitrariamente complejas sin que el código se vuelva inmanejable —divide y vencerás—. Su reactividad mantiene la interfaz sincronizada con el estado de forma declarativa: se describe *qué* mostrar, no *cómo* actualizar la pantalla paso a paso. El uso de TypeScript agrega verificación de tipos que detecta errores antes de ejecutar el código. El resultado es una interfaz mantenible y testeable.

**Inertia.js.**[^inertia] Inertia es la pieza que une el backend y el frontend, y su elección es una de las decisiones de arquitectura centrales del proyecto. Permite una experiencia de página única (SPA) —navegación fluida, sin recargas completas de página— sin tener que construir ni mantener una API separada (REST o GraphQL) entre ambos extremos. Una API desacoplada habría duplicado esfuerzo y superficie de error: serialización explícita de cada recurso, versionado, autenticación aparte, y un ruteo y manejo de estado espejados en el cliente. Con Inertia, los controladores de Laravel devuelven directamente componentes de Vue con sus datos; el servidor sigue siendo la única fuente de verdad y desaparece la capa intermedia. El compromiso —un acoplamiento estrecho entre frontend y backend, poco apto si se necesitara exponer una API pública o servir a múltiples clientes distintos— es plenamente aceptable para una aplicación relativamente simple, con volúmenes de tráfico modestos.

**MariaDB 12.**[^mariadb] MariaDB es un fork de MySQL completamente de código abierto, con buen rendimiento y robustez. Su naturaleza relacional y de **esquema estricto** acompaña deliberadamente las decisiones de diseño del modelo de datos (véase el Apéndice A): claves foráneas y restricciones que hacen cumplir la integridad de la información en la propia base, y no solo en la aplicación. Sus garantías transaccionales (ACID) importan para operaciones que deben ocurrir de forma atómica, como la escritura de la asignación del sorteo. Que sea libre y sin costo de licencia es, además, coherente con el objetivo de que MTAV pueda desplegarse y difundirse sin ataduras.

**Docker.**[^docker] Todo el entorno —PHP, servidor web, base de datos, servidor de tiempo real y compilación de recursos— está containerizado con Docker. Esto brinda paridad entre desarrollo y producción (elimina el clásico "en mi máquina funciona"), reproducibilidad y una puesta en marcha sencilla, y aísla cada servicio del sistema anfitrión. Sobre esa base, el script `./mtav` envuelve las operaciones habituales (levantar el entorno, correr pruebas, acceder a la base, etc.) en una interfaz de comandos unificada.

**Laravel Reverb.**[^reverb] Para las actualizaciones en tiempo real, MTAV usa Reverb, un servidor WebSocket propio integrado de forma nativa con el sistema de difusión de Laravel. Se optó por **autoalojarlo** en lugar de depender de un servicio externo de pago (como Pusher o Ably) por tres motivos: no introduce una dependencia externa ni un costo recurrente, mantiene los datos dentro de la propia infraestructura —relevante para la privacidad de la información de los cooperativistas— y otorga control total sobre el servicio. Es la pieza que habilita la naturaleza compartida y en tiempo real descrita en el Capítulo @cap-intro.

**Bibliotecas y herramientas de apoyo.** Alrededor de ese núcleo, el proyecto se apoya en un conjunto de piezas auxiliares, cada una con un rol puntual. Entre ellas:

- **Laravel Sanctum**[^sanctum] — autenticación de sesión vía cookies.
- **Tailwind CSS 4 + Reka UI**[^tailwind-reka] — estilos utilitarios y componentes de interfaz accesibles y sin estilo predefinido; base sobre la que se construye la accesibilidad (véase la Sección @sec-accesibilidad).
- **Vite**[^vite] — empaquetado y servidor de desarrollo del frontend.
- **Laravel Echo + Pusher JS**[^echo] — cliente que recibe, en el navegador, los eventos en tiempo real emitidos por Reverb.
- **Ziggy**[^ziggy] — expone las rutas de Laravel al frontend, evitando duplicar las URLs (integrado con Inertia.js).
- **`php-ffmpeg`**[^phpffmpeg] — procesamiento de medios (miniaturas, audio y video).
- **`devvir/laravel-instant-api` y `devvir/laravel-resource-tools`** — dos paquetes desarrollados en el marco de este trabajo, que sostienen la generación de formularios y la serialización de recursos (véase la Sección @sec-recursos).
- **Pruebas:** Pest/PHPUnit (backend), Vitest + Vue Test Utils (frontend) y Playwright (extremo a extremo).[^testing]
- **Calidad de código:** PHP Insights y Pint (PHP), ESLint y Prettier (frontend), ejecutados mediante hooks de Git.[^calidad]

Las herramientas de pruebas y calidad se detallan en el Apéndice E.

[^laravel]: Laravel: https://laravel.com
[^vue]: Vue.js: https://vuejs.org — TypeScript: https://www.typescriptlang.org
[^inertia]: Inertia.js: https://inertiajs.com
[^mariadb]: MariaDB: https://mariadb.org
[^docker]: Docker: https://www.docker.com
[^reverb]: Laravel Reverb: https://reverb.laravel.com
[^sanctum]: Laravel Sanctum: https://laravel.com/docs/sanctum
[^tailwind-reka]: Tailwind CSS: https://tailwindcss.com — Reka UI: https://reka-ui.com
[^vite]: Vite: https://vite.dev
[^echo]: Laravel Echo: https://laravel.com/docs/broadcasting — Pusher JS: https://github.com/pusher/pusher-js
[^ziggy]: Ziggy: https://github.com/tighten/ziggy
[^phpffmpeg]: PHP-FFMpeg: https://github.com/PHP-FFMpeg/PHP-FFMpeg
[^testing]: Pest: https://pestphp.com — PHPUnit: https://phpunit.de — Vitest: https://vitest.dev — Vue Test Utils: https://test-utils.vuejs.org — Playwright: https://playwright.dev
[^calidad]: PHP Insights: https://phpinsights.com — Laravel Pint: https://laravel.com/docs/pint — ESLint: https://eslint.org — Prettier: https://prettier.io


## El ciclo de vida de un proyecto [en la plataforma]{.cambio} {#sec-ciclo}

[#19: título cambiado. "De un proyecto cooperativo" sonaba a todo el proyecto de la cooperativa; la sección recorre un proyecto dentro de la plataforma]{.rev #rev-19}

[Las funcionalidades se presentan en el orden en que aparecen naturalmente en un proyecto: primero la configuración, luego la incorporación de las familias, la carga de preferencias y la comunicación, y finalmente el sorteo, que por su centralidad se trata aparte en la Sección @sec-sorteo-funcional.]{.quitado}

::: cambio
En esta sección se describen las funcionalidades de la plataforma en el orden en que se usan dentro de un proyecto, que es el que imponen sus propias dependencias: antes de que una familia pueda expresar sus preferencias o compartir fotos, una persona administradora tiene que crear el proyecto y sus viviendas (§@sec-configuracion) y dar de alta a las familias e invitar a sus integrantes (§@sec-familias); y solo cuando las familias están incorporadas y sus preferencias cargadas puede ejecutarse el sorteo. Sobre esa base se presentan el plano del proyecto (§@sec-plano), la gestión de eventos (§@sec-eventos) y de medios (§@sec-medios), y las notificaciones en tiempo real (§@sec-notificaciones). La carga de preferencias y el sorteo, por su centralidad, se tratan aparte en la Sección @sec-sorteo-funcional. [#20 y #31: el orden es el de uso de la plataforma, no el de un proceso cooperativo real; se nombran sus subsecciones. La primera frase retoma la sugerida por el tutor]{.rev #rev-20}
:::

### Configuración inicial: el proyecto y sus tipos de vivienda {#sec-configuracion}

Un proyecto lo crea un superadministrador, que designa a uno o más administradores (§@sec-roles). A partir de ahí, el administrador lo configura.

El primer paso es definir los **tipos de vivienda**. Un tipo agrupa unidades equivalentes a los efectos del sorteo —por ejemplo, "2 dormitorios" o "3 dormitorios"—; su papel no es meramente descriptivo: **particiona el problema de asignación**. Cada familia se asocia a un tipo y competirá únicamente por las unidades de ese tipo, de modo que el sorteo del proyecto es en realidad un sorteo independiente por cada tipo (la justificación de este modelo está en el Apéndice A). Dentro de cada tipo, el administrador carga las **unidades** concretas, identificadas por un código o nombre (p. ej. "Ap. 101"). Cada unidad permanece sin familia asignada hasta que se ejecuta el sorteo.

### Incorporación de familias y cooperativistas {#sec-familias}

El administrador registra las **familias** del proyecto y asigna a cada una un tipo de vivienda. La familia —y no el individuo— es la unidad de asignación: es la familia la que ordena las preferencias y la que recibe una unidad (Apéndice A).

Los **cooperativistas** se incorporan como integrantes de una familia. El acceso es únicamente por invitación: no existe registro público. Superadministradores y administradores crean las familias e invitan a sus integrantes, y un integrante puede a su vez invitar a otros de su propia familia; cada persona recibe una invitación y completa su registro —define su contraseña— para activar la cuenta.

Todos los usuarios —cooperativistas, administradores y superadministradores— comparten un mismo mecanismo de autenticación y una única tabla de usuarios; las diferencias de rol se resuelven de forma transparente (Apéndice A).

### El plano del proyecto {#sec-plano}

Opcionalmente, un proyecto puede incluir un **plano** espacial: una representación visual e interactiva de la disposición de las unidades. Se construye con SVG y se estructura en dos niveles: el plano en sí (el lienzo, con su polígono de contorno y sus dimensiones) y una serie de ítems, cada uno un polígono asociado a una unidad concreta.

Funcionalmente, el administrador edita el plano —ubica cada unidad mediante arrastrar y soltar— y los cooperativistas lo consultan para ubicar las unidades antes de ordenar sus preferencias. El redimensionado de las formas y el soporte multinivel (pisos) quedan como trabajo futuro (§@sec-tf-plano). La arquitectura de componentes SVG, el escalado responsivo y la mecánica del editor se detallan en el Apéndice D.

### Gestión de eventos {#sec-eventos}

El administrador publica **eventos** de cualquier índole —reuniones, asambleas, actividades sociales, celebraciones—, presenciales o en línea. Cada evento tiene título, descripción, ubicación y fechas de inicio y fin (con una duración implícita de una hora si no se indica el fin), y un estado que el sistema deriva solo: próximo, en curso o finalizado. Un evento puede estar en borrador o publicado —solo los publicados son visibles para los cooperativistas—, y cuando admite confirmación de asistencia, cada cooperativista puede aceptar o rechazar la invitación y la lista de asistentes queda visible para todo el proyecto.

Un detalle de diseño relevante es que **el sorteo mismo se modela como un evento**, de tipo *lottery*, lo que unifica bajo un mismo modelo su programación, publicación y visibilidad con las del resto de los eventos. A diferencia de los demás, el evento de sorteo **se crea automáticamente junto con el proyecto**: el administrador no lo crea ni lo elimina; solo puede **agendarlo o reagendarlo** (fijar o cambiar su fecha) y editar su descripción —el título es fijo—, y solo mientras el sorteo no haya comenzado a ejecutarse. Su ejecución se trata en la Sección @sec-sorteo-funcional.

### Gestión de medios {#sec-medios}

Cualquier integrante del proyecto puede compartir **medios**, clasificados en cuatro categorías: imágenes, video, audio y documentos. Las imágenes y los videos alimentan una galería del proyecto; los documentos quedan disponibles para su descarga. Cada archivo queda asociado a quien lo subió y a la fecha en que se publicó. Una vez publicado, el archivo en sí no puede modificarse: solo puede editarse su descripción o eliminarse.

El procesamiento de medios —por ejemplo, la generación de miniaturas— se apoya en FFmpeg (Sección @sec-stack).

### Notificaciones y actualizaciones en tiempo real {#sec-notificaciones}

El sistema mantiene informados a los usuarios mediante **notificaciones** dentro de la aplicación. Una notificación puede dirigirse a una persona concreta (privada), a todos los integrantes de un proyecto, o al conjunto del sistema (global). Cada usuario ve únicamente las notificaciones que le corresponden, y se distinguen dos estados por notificación y por usuario: leída o no leída.

Estas notificaciones y otras actualizaciones se entregan **en tiempo real**: cuando ocurre algo relevante —se publica un evento, finaliza el sorteo, se incorpora un integrante—, los usuarios conectados lo ven sin recargar la página, mediante WebSockets (Reverb, Sección @sec-stack). La arquitectura de eventos y *listeners* que desacopla la emisión de estas actualizaciones se describe en la Sección @sec-arquitectura.


## El sorteo {#sec-sorteo-funcional}

El sorteo es el punto culminante de la etapa de construcción de una cooperativa y la razón por la que MTAV fue concebido originalmente: una vez cargadas las preferencias, asigna cada unidad a una familia de forma justa, óptima y verificable. Marca, además, el paso de la construcción a la convivencia. Esta sección describe el sorteo funcionalmente —cómo se usa y qué garantiza—[: el modelo de preferencias con que cada familia expresa lo que quiere (§@sec-preferencias-funcional), lo que el algoritmo garantiza (§@sec-garantias-funcional) y cómo se ejecuta, queda fijo y puede auditarse (§@sec-ejecucion-funcional)]{.nuevo}[; su]{.quitado}[. Su]{.nuevo} fundamento matemático[ (Secciones @sec-problema-asignacion a @sec-formulacion)]{.nuevo}, los algoritmos y la mejora de rendimiento que aporta este trabajo[ (Secciones @sec-cuello-fase1 a @sec-resultados) y la mecánica de ejecución (Secciones @sec-ejecucion y @sec-auditoria)]{.nuevo} se desarrollan en el Capítulo @cap-sorteo. [#21: vinculación con sus subsecciones y con el Capítulo @cap-sorteo]{.rev #rev-21}

### El modelo de preferencias {#sec-preferencias-funcional}

Cada familia expresa sus preferencias ordenando las unidades de su tipo, de la más deseada a la menos deseada, mediante una interfaz de arrastrar y soltar, accesible desde cualquier dispositivo. Cualquier integrante de una familia puede consultar las preferencias de su familia y modificarlas (§@sec-vision). No es necesario ordenar todas las unidades manualmente: las que un cooperativista no posicione de forma explícita se incorporan al final del ordenamiento, de modo que la preferencia de cada familia siempre cubre la totalidad de las unidades de su tipo.

Las preferencias pueden modificarse en cualquier momento hasta que el administrador ejecuta el sorteo; en ese instante quedan bloqueadas. Cada familia ve únicamente sus propias preferencias.

### Qué garantiza el algoritmo {#sec-garantias-funcional}

El algoritmo no asigna al azar ni por orden de llegada: encuentra una asignación **óptima** según dos criterios, en orden de prioridad estricta. Primero, la **equidad**: procura que la familia con el peor resultado quede lo mejor posible, evitando que alguna reciba un resultado desproporcionadamente malo respecto de las demás. Segundo, y sin ceder nada de esa equidad, la **satisfacción global**: entre todas las asignaciones igualmente equitativas, elige la que deja al conjunto lo más conforme posible —la mayor cantidad de primeras opciones, luego segundas, y así sucesivamente—.

El resultado es **reproducible dadas las preferencias** —solo los empates se resuelven al azar, de forma imparcial— y **verificable** (puede auditarse mostrando las preferencias y la asignación). La definición precisa de estos criterios, su formulación matemática y sus propiedades se desarrollan en el Capítulo @cap-sorteo.

### Ejecución, inmutabilidad y auditoría {#sec-ejecucion-funcional}

Cuando el proceso está listo, el administrador ejecuta el sorteo. El sistema bloquea las preferencias, resuelve la asignación —de forma independiente por cada tipo de vivienda— y escribe todas las asignaciones de una sola vez. El resultado es **definitivo**: no se edita ni se negocia. Solo en situaciones excepcionales un superadministrador puede invalidar la ejecución completa para volver a correrla (§@sec-ciclo-mtav).

Cada ejecución queda registrada en un **registro de auditoría** permanente, que conserva las preferencias consideradas y el resultado producido. Ese registro es lo que hace del sorteo un proceso transparente y no repudiable: cualquiera puede verificar, después del hecho, que el resultado se corresponde con las preferencias ingresadas. El mecanismo de ejecución, las garantías de inmutabilidad y el formato del registro de auditoría se detallan en el Capítulo @cap-sorteo.


## Accesibilidad {#sec-accesibilidad}

Esta sección explica por qué la accesibilidad es un requisito de primer orden en MTAV: el perfil real de los usuarios (§@sec-perfil), las medidas concretas implementadas (§@sec-decisiones-accesibilidad) y la internacionalización (§@sec-i18n).

### El perfil del usuario objetivo {#sec-perfil}

La base de usuarios de una cooperativa de vivienda es heterogénea e incluye a personas para quienes una interfaz mal diseñada es una barrera real: adultos mayores, personas con discapacidad y personas que acceden desde dispositivos de gama baja o con conectividad limitada. En una plataforma cuyo propósito es, justamente, que las familias participen directamente (Capítulo @cap-intro), la accesibilidad no es un agregado posterior sino un **requisito de primer orden**: si una parte de los cooperativistas no puede usar la herramienta, la autogestión que MTAV promete se quiebra. Por eso las decisiones de accesibilidad se tomaron desde el diseño y no como una capa cosmética posterior.

### Decisiones de diseño para accesibilidad {#sec-decisiones-accesibilidad}

Las medidas concretas adoptadas son:

- **Base sobre componentes accesibles.** La interfaz se construye sobre Reka UI, una biblioteca de componentes que implementa las pautas de accesibilidad en los elementos interactivos —navegación por teclado, gestión del foco y atributos ARIA en menús, diálogos y campos—. Sobre esa base se agregan atributos ARIA explícitos donde hacen falta: etiquetas, estados de validación de formularios y regiones activas que anuncian cambios.
- **Tipografía amplia y fluida.** El tamaño de texto base es deliberadamente mayor que el habitual y escala con el tamaño de la pantalla, de modo que el texto resulte legible sin obligar al usuario a hacer zoom.
- **Modo de alto contraste y temas.** Cada usuario puede elegir entre tema claro, oscuro o el del sistema, y se incluye un modo de **alto contraste**, útil para personas con baja visión.
- **Respeto por la reducción de movimiento.** Si el usuario configuró en su sistema operativo la preferencia de "reducir movimiento", la aplicación desactiva transiciones y animaciones, evitando efectos que pueden causar molestias.
- **HTML semántico y áreas táctiles amplias**, pensadas para el uso con el dedo en pantallas pequeñas.

Estas decisiones toman como referencia las pautas **WCAG AA**, adoptadas como piso mínimo dado el perfil de usuario. La verificación formal de conformidad y el soporte completo de lectores de pantalla quedan como trabajo futuro (Sección @sec-trabajo-futuro).

### Internacionalización {#sec-i18n}

La aplicación está completamente internacionalizada: hoy funciona en español y en inglés, y toda su interfaz —incluidos mensajes, formularios y correos— proviene de diccionarios de traducción. Sumar un nuevo idioma no requiere tocar el código: basta con agregar los diccionarios correspondientes (un archivo por idioma, más los archivos temáticos de Laravel). Esto amplía el alcance de la herramienta más allá del contexto uruguayo.


## Diseño mobile-first {#sec-mobile}

Esta sección describe el enfoque de diseño para dispositivos móviles —el medio principal de acceso esperado— y las decisiones que lo materializan.

### Enfoque responsive y decisiones de diseño {#sec-responsive}

La aplicación fue diseñada **mobile-first**: primero para la pantalla del teléfono y luego extendida a pantallas mayores, no al revés. En la práctica, esto se apoya en un sistema de estilos cuyo punto de partida es el diseño móvil, y en un dimensionamiento **fluido**: tanto la tipografía como los espaciados escalan de forma continua con el tamaño de la ventana, en lugar de saltar entre unos pocos diseños fijos. El resultado es una interfaz que se adapta con naturalidad a cualquier tamaño de pantalla.

### Implicancias técnicas y de experiencia de usuario {#sec-implicancias-mobile}

La decisión responde al perfil de uso real: los cooperativistas acceden mayoritariamente desde el **celular**, muchas veces de gama baja. Priorizar el móvil garantiza que la experiencia más frecuente —la que usan las familias para ingresar sus preferencias, consultar el plano o confirmar asistencia a un evento— sea la mejor cuidada, y no una versión reducida de una interfaz pensada para el escritorio.

Este enfoque se refuerza con las decisiones de accesibilidad de la sección anterior: la tipografía amplia, las áreas táctiles y el diseño fluido sirven al mismo objetivo de que cualquier persona, en cualquier dispositivo, pueda participar. En pantallas grandes, los mismos diseños se expanden para aprovechar el espacio disponible, pero la referencia primaria sigue siendo el teléfono.

