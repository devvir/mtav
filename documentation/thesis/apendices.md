```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Apéndices

> [NOTA: salvo el Apéndice A, que ya incluye la derivación del modelo de datos, el resto de los
> apéndices son por ahora **esbozos**: cada uno indica qué contendrá, para completarse más adelante.]

Los apéndices son material de referencia. No están pensados para leerse de principio a fin: cada uno amplía un tema que las Partes I a IV presentan de forma suficiente para comprender el trabajo. Un lector con interés particular en un tema —el modelo de datos, la formulación matemática, la planificación— encontrará aquí el detalle; quien no, puede omitirlos sin perder el hilo.

**Guía rápida:**

- **A — Modelo de datos y recursos JSON.** Por qué el esquema tiene la forma que tiene (derivación), diagrama entidad-relación completo y definiciones tabla por tabla, y el glosario de los recursos JSON que exponen esos modelos al frontend.
- **B — Autenticación, roles y permisos.** Mecanismo de autenticación y flujo de invitación y registro; matriz de qué puede hacer cada rol, por recurso y acción.
- **C — El sorteo: modelos matemáticos y registro de auditoría.** Modelos GMPL completos de la formulación de la Parte III y formato del registro de auditoría de cada ejecución.
- **D — Formato de datos del plano espacial** y arquitectura de sus componentes.
- **E — Testing y aseguramiento de calidad.**
- **F — Uso de IA en el proyecto.**
- **G — Planificación y artefactos previos** (casos de uso, requisitos, etc.).
- **H — Manuales de usuario** (uno por rol).

---

## Apéndice A — Modelo de datos y recursos JSON

### A.1 Del problema al esquema: por qué el modelo crece

La premisa de MTAV en línea es engañosamente simple: *"hagamos una aplicación para que las propias familias ingresen sus preferencias y se ejecute el sorteo"*. A primera vista no parece necesitar mucho más que una lista de familias y una lista de viviendas. Sin embargo, el modelo de datos real tiene más de una docena de tablas. Esta sección deriva ese modelo paso a paso, mostrando cómo **cada entidad aparece obligada por una necesidad concreta**. Es el lugar donde vive, de forma cohesiva, la justificación completa del modelo; el resto del documento se apoya en esta derivación en lugar de repetirla.

**Punto de partida: preferencias.** El sorteo asigna viviendas a partir de las preferencias. Lo mínimo indispensable es, entonces, tres cosas: una **familia**, un conjunto de **unidades** (las viviendas concretas) y una forma de registrar cómo esa familia *ordena* esas unidades. Ese ordenamiento es una relación muchos-a-muchos entre familias y unidades con un dato extra —la posición en el ranking—, que en el esquema es la tabla `unit_preferences` con su columna `order`. Nótese ya una decisión de fondo: **la unidad que recibe una vivienda es la familia, no la persona**. Una familia obtiene exactamente una unidad.

**Una familia es varias personas: los miembros.** Una familia no es un único usuario: varias personas —integrantes de un mismo núcleo— pueden interactuar con la aplicación en su nombre. Necesitamos entonces **miembros** (`Member`; son los *cooperativistas* de las Partes I y II — aquí se usa el nombre que llevan en el modelo), cada uno perteneciente a una familia (`family_id`). Una familia tiene muchos miembros; un miembro pertenece a una familia. Las preferencias, sin embargo, siguen siendo de la *familia*, no de cada miembro: cualquier integrante las edita, pero el sujeto del sorteo es el núcleo familiar.

**Todos se autentican: la tabla `users`.** Los miembros inician sesión. Pero también lo hacen quienes configuran el proyecto (los administradores) y quienes operan la plataforma (los superadministradores). Todos comparten exactamente el mismo mecanismo de autenticación: correo y contraseña, invitaciones (salvo superadministradores), restablecimiento de clave. En lugar de tres tablas de autenticación paralelas, se unifican en una sola tabla **`users`**, y el tipo se distingue con un flag `is_admin`; los miembros, además, llevan su `family_id`. El superadministrador es un usuario administrador (a nivel de la base de datos) cuyo correo figura en una lista de configuración del entorno. (El detalle de esta "pseudo-herencia de tabla única" y sus alternativas se trata más abajo, en A.3.)

**Todo pertenece a una cooperativa: los proyectos.** MTAV en línea sirve a muchas cooperativas a la vez. Cada familia, cada unidad, cada evento pertenece a un **proyecto** concreto (el emprendimiento de una cooperativa). Aparece así la entidad `Project`, y las familias y unidades pasan a pertenecer a un proyecto. Esta pertenencia no es solo organizativa: es la que permite que la aplicación aísle por completo los datos de una cooperativa de los de otra (el mecanismo de *scoping* por proyecto se detalla en la Sección 21).

**Alguien crea y gestiona todo esto: los administradores.** Las familias, las unidades y los eventos no se crean solos. Hace falta un **administrador** (`Admin`) que configure el proyecto. Los administradores se relacionan con los proyectos, y como un administrador puede gestionar varios proyectos y un proyecto puede tener varios administradores, esa relación es muchos-a-muchos, a través de la tabla `project_user` (con un campo `active`). Los miembros se vinculan a su proyecto por esa misma tabla —normalmente a un único proyecto activo—, lo que mantiene una sola vía de pertenencia usuario–proyecto.

**Las unidades no son intercambiables: los tipos de unidad.** Un apartamento de dos dormitorios y uno de tres no son comparables, y una familia a la que le corresponde uno de tres dormitorios solo debe seleccionar sus preferencias por unidades de tres dormitorios. Por eso cada unidad tiene un **tipo** (`UnitType`) y cada familia tiene asignado un tipo. Esto revela algo importante sobre el sorteo: el sorteo "global" de un proyecto es en realidad un conjunto de **sub-sorteos independientes, uno por tipo de unidad**, en los que las familias de ese tipo compiten por las unidades de ese tipo. El tipo de unidad no es un adorno descriptivo: es lo que particiona el problema. (La mecánica del sorteo por tipos y la redistribución de remanentes se tratan en la Sección 17.)

**El resultado: la asignación.** Terminado el sorteo, cada familia recibe exactamente una unidad. Eso se registra en la propia unidad, mediante su `family_id`: una unidad pertenece (tras el sorteo) a una familia, y una familia tiene una unidad. Antes del sorteo ese vínculo es nulo. La asignación no es una tabla aparte: es este vínculo el que constituye la fuente de verdad del resultado.

**Y sigue creciendo.** Sobre esta espina dorsal —familias, miembros, usuarios, proyectos, administradores, tipos de unidad, unidades y preferencias— se apoyan las demás piezas, cada una respondiendo a una necesidad igual de concreta: los **eventos** y su confirmación de asistencia (asambleas, el propio sorteo), los **medios** (documentos e imágenes del proyecto), el **plano** espacial, las **notificaciones** y el **registro de auditoría** del sorteo. Cada una se describe en su sección correspondiente.

Dos observaciones cierran la derivación:

- **Las preferencias son dinámicas.** El modelo permite que las unidades se agreguen o quiten y que las familias cambien de tipo. Por eso la tabla `unit_preferences` **no** es la fuente de verdad de las preferencias: la lista válida de preferencias de cada familia se resuelve en tiempo de ejecución, contemplando esos cambios (se detalla en la Sección 17). Esta flexibilidad es, ella misma, una consecuencia de haber modelado el problema con entidades separadas en lugar de una planilla rígida.
- **Las reglas de acceso surgen del propio modelo.** Que exista un administrador que crea, un miembro que solo ordena las preferencias de *su* familia y un superadministrador que supervisa no es una capa añadida a posteriori: es una lectura directa de las relaciones anteriores. Cómo se aplican esas reglas en cada capa se detalla en la Sección 21.

En síntesis: la complejidad del modelo no es accidental ni ornamental. Cada tabla es la respuesta mínima a un requisito real que la premisa inicial —"que las familias ingresen sus preferencias"— trae implícito.

### A.2 Esquema relacional completo

[NOTA: diagrama entidad-relación completo (todas las tablas) + definiciones tabla por tabla (columnas, tipos, claves foráneas, restricciones). Generar el ER a partir del esquema real.]

### A.3 Pseudo-herencia de tabla única para usuarios

[NOTA: la tabla `users` con `is_admin` y `family_id`; subclases `Admin`/`Member` con global scopes; superadmin por lista de correos en config; compromisos frente a una columna discriminadora o tablas separadas.]

### A.4 Borrado suave y migraciones

[NOTA: uso de soft deletes (en particular en el flujo del sorteo) y las migraciones como historial versionado y reproducible del esquema.]

### A.5 Glosario de recursos JSON / superficie de la API

Los modelos anteriores no viajan al frontend tal cual: se exponen como recursos JSON (Sección 22). Esta sección es el glosario de esa superficie.

[NOTA: cada tipo de recurso, sus campos y los permisos `can` que expone; referencia para desarrolladores. Incluye la descripción de los dos paquetes propios (`laravel-instant-api`, `laravel-resource-tools`).]

---

## Apéndice B — Autenticación, roles y permisos

### B.1 Autenticación

[NOTA: mecanismo de autenticación (Laravel Sanctum, sesión por cookies); flujo de invitación y registro (registro solo por invitación, `invitation_accepted_at`); restablecimiento de contraseña y verificación de correo.]

### B.2 Matriz de roles y permisos

[NOTA: tabla de qué puede hacer cada rol (superadmin, administrador, cooperativista), organizada por recurso y acción; se corresponde con las políticas descritas en la Sección 21.]

---

## Apéndice C — El sorteo: modelos matemáticos y registro de auditoría

### C.1 Modelos GMPL completos

[NOTA: archivos de modelo GMPL (GNU MathProg) completos para la Fase 1 y la Fase 2 (y el modelo de selección de unidades para el desbalance), anotados con el mapeo entre la notación matemática de la Parte III y el código.]

### C.2 Formato del registro de auditoría

[NOTA: esquema de la tabla `lottery_audits`; tipos de registro (INIT, GROUP_EXECUTION, PROJECT_EXECUTION, INVALIDATE, FAILURE); agrupación por UUID; salida de muestra anotada de una ejecución completa.]

---

## Apéndice D — Formato de datos del plano espacial

[NOTA: cómo se almacenan los polígonos (columna JSON) y los ítems del plano (`PlanItem`, con `floor`); coordenadas; y la arquitectura de componentes SVG (Plan → Canvas → Item → Polygon), el escalado responsivo y el editor de arrastrar y soltar. Nota: redimensionado y multinivel son trabajo futuro (Sección 10.2).]

---

## Apéndice E — Testing y aseguramiento de calidad

[NOTA: estrategia de pruebas en capas (Pest/PHPUnit backend, Vitest + Vue Test Utils frontend, Playwright end-to-end); hooks de Git como quality gates; PHP Insights y Pint, ESLint y Prettier; la fixture `universe.sql` y sus compromisos.]

---

## Apéndice F — Uso de IA en el proyecto

[NOTA: cómo, dónde y en qué medida se usó asistencia de IA en el desarrollo; criterios aplicados (p. ej., se revisó cada línea no escrita por el autor, salvo las suites de tests). Nota de transparencia metodológica.]

---

## Apéndice G — Planificación y artefactos previos

[NOTA: artefactos de ingeniería previos al desarrollo, elaborados para dejar constancia del análisis. Anclado en los **casos de uso**. Candidatos ("usual suspects") a incluir —decidir cuáles—:
- Casos de uso: actores, diagrama de casos de uso y descripciones de los casos principales.
- Requisitos funcionales y no funcionales.
- Modelo de dominio / conceptual (se relaciona con el Apéndice A).
- Diagramas de secuencia de los flujos clave (p. ej., ejecución del sorteo).
- Wireframes / mockups de las pantallas principales.
- (Opcional: alcance, metodología, cronograma.)]

---

## Apéndice H — Manuales de usuario

[NOTA: un manual por rol —superadministrador, administrador, cooperativista— en un único apéndice. Son parte de la aplicaión, accessible a cualquier usuario, y se portan aquí tal cual.]

