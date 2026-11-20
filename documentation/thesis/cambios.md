# Cambios en esta versión

Esta sección y las marcas de color son temporales: indican qué cambió desde la revisión anterior y por qué, para no tener que releer el documento entero. Desaparecen en la versión de entrega.

- [Texto agregado]{.nuevo}
- [Texto reescrito]{.cambio}
- [Texto eliminado]{.quitado}
- [#15: motivo del cambio, con el número del comentario que lo originó]{.rev}

Cada comentario y cada sugerencia de edición se transcriben textualmente, porque al volver a subir el documento los comentarios y las sugerencias de Google Docs pueden perderse.

## Revisión del 18 de octubre de 2026

### #15 · Carátula, título

**Texto comentado:** "Asignación de viviendas en cooperativas: una plataforma web y móvil de autogestión"

**Comentario:** "Me gustó mucho el título"

**Qué se hizo:** sin cambios.

### #16 · Resumen

**Texto comentado:** "no negociable."

**Comentario:** "más abajo se explica este concepto, pero no sé si queda del todo claro sin aclaración. No se me ocurre otra forma de decirlo, quizás poner entre paréntesis o en pie de página una breve explicación"

**Qué se hizo:** se agregó una breve aclaración entre paréntesis en el Resumen, que remite a la definición de la Sección 1.3.

### #17 · 1.2 El proceso de asignación de viviendas

**Texto comentado:** "reales" (en "fue aplicado con éxito en varios proyectos reales")

**Comentario:** "Agregaría citas aquí"

**Qué se hizo:** se agregaron las citas: Prino, Sánchez y Cancela (2016) para el desarrollo del método y Fierro (2024) para su aplicación en proyectos reales.

### #18 · 5.1 Panorama general

**Texto comentado:** "propio" (en "mediante un servidor propio", antes de la descripción de Laravel)

**Comentario:** "Creo que falta algo general aquí, del estilo de qué criterios conceptuales se tomaron para elegir las distintas componentes del stack (software libre, soporte, comunidad que está atrás, conocimiento tuyo personal, lo que fuera). No sólo qué se eligió. Luego para cada elemento das una explicación concreta, pero sería bueno tener unos puntos generales/transversales."

**Qué se hizo:** se agregó un párrafo con los tres criterios generales de elección (software libre; madurez y comunidad; conocimiento previo del autor), antes de la justificación de cada pieza.

### #19 · 6. Título del capítulo

**Texto comentado:** "proyecto cooperativo" (en "El ciclo de vida de un proyecto cooperativo")

**Comentario:** "Creo que hay que ajustar, "un proyecto cooperativo" suena a algo mucho más extenso que el proceso de asignación. Quizás "El ciclo de vida del proceso de asignación de unidades de un proyecto cooperativo"."

**Qué se hizo:** el capítulo pasó a llamarse "El ciclo de vida de un proyecto en la plataforma". Se tomó la idea del comentario, pero no el título sugerido, porque el capítulo no recorre el proceso de asignación sino un proyecto dentro de la plataforma: su configuración, las familias, el plano, los eventos, los medios y las notificaciones. Se ajustó en el mismo sentido la presentación de la Parte II.

### #20 · 6. Introducción del capítulo

**Texto comentado:** "Las funcionalidades se presentan en el orden en que aparecen naturalmente en un proyecto: primero la configuración, luego la"

**Comentario:** "Este párrafo no está muy enganchado con las secciones siguientes, quizás hay que revisar. Creo que sería bueno también decir algo de cómo llegás a esta definición del ciclo de vida, por los materiales publicados de MTAV, conversación con el tutor, conocimiento personal del proceso, lo que fuere."

**Qué se hizo:** se reescribió el párrafo de apertura: aclara que el orden es el de uso de la plataforma —el que imponen sus dependencias: sin proyecto, viviendas y familias no hay preferencias, y sin preferencias no hay sorteo—, no el de un proceso cooperativo real, y nombra cada sección del capítulo.

### #21 · 7. El sorteo, introducción

**Texto comentado:** "sorteo funcionalmente —cómo se usa y qué garantiza—; su fundamento matemático, los algoritmos y la mejora de rendimiento que aporta este trabajo s[e desarrollan en la Parte III]"

**Comentario:** "falta la vinculación con las secciones."

**Qué se hizo:** el párrafo de apertura nombra ahora las secciones del capítulo (§7.1 a §7.3) y los capítulos de la Parte III que desarrollan cada aspecto.

### #22 · 13. El cuello de botella de la Fase 1

**Texto comentado:** "El efecto es real y medible. En los *benchmarks* de la solución original (escenario aleatorio) aparecen los primeros *timeouts* ya en tamaños de 25 a 30 familias […] mostraron resultados"

**Comentario:** "No me queda claro aquí en qué hardware (y s. operativo) se corrieron esos benchmarks. Tampoco hay muchos más detalles (como se define ese "escenario aleatorio". Entiendo quizás aquí no querés poner tanta info, pero debería estar en la sección que habla más en detalle, es la 15, verdad?"

**Qué se hizo:** se define el escenario aleatorio y se indica el equipo de las mediciones, con remisión al Capítulo 15, donde está el detalle.

### #23 · 15. Resultados empíricos

**Texto comentado:** "(mezcla de unidades populares e impopulares, que simula el comportamiento real), idénticas y opuestas (los dos casos degenerados)—"

**Comentario:** "Haría falta más información de los escenarios, como son generados. Tal como está escrito, es muy breve y no queda claro. También hace falta información sobre la plataforma en que se hicieron las pruebas computacionales y se midieron los tiempos"

**Respuesta en el mismo hilo:** "Si bien se puede hacer mención (y correspondería) al apéndice con los resultados e infor más detallada, igual algo hay que explicar aquí para facilidad de lectura y comprensión de los resultados."

**Qué se hizo:** se reescribió la presentación de los benchmarks: cómo se generan las instancias, una definición breve de cada escenario y un párrafo sobre la plataforma. El detalle completo queda en el Apéndice C.3. Se quitó la afirmación de que el escenario realista "simula el comportamiento real": no hay unidades populares para el conjunto, porque cada familia sortea las suyas.

### #24 · C.3 Resultados de rendimiento, escenario realista

**Texto comentado:** "realistas (mezcla de unidades populares e impopulares que simula el comportamiento observado),"

**Comentario:** "se necesitaría más info de este escenario, ya que tal como está escrito, no es reproducible - cuantas o qué porcentaje de unidades se toman como "populares" vs "impopulares" ; y cómo se sortea, es uniforme dentro de las "populares" y luego dentro de las "impopulares"?"

**Qué se hizo:** se describe la generación del escenario realista paso a paso (porcentajes, pesos de cada tramo, tratamiento de repetidos), de modo que pueda reproducirse.

### #25 · C.3 Resultados de rendimiento, escenario opuesto

**Texto comentado:** "rankings en espejo"

**Comentario:** "No es muy claro eso que quiere decir. Una familia sortea un ranking al azar, otra exactamente el orden en espejo? (y se hace mitad de familias al azar y la otra mitad en espejo?). O se sortea un único orden y mitad de las familias tiene exactamente ese, y mitad en espejo?"

**Qué se hizo:** es lo segundo: se fija un único orden, que no se sortea; la mitad de las familias lo adopta tal cual y el resto, invertido. Se describe así en el Capítulo 15 y en el Apéndice C.3.

### #26 · C.3 Resultados de rendimiento, solución original

**Texto comentado:** "La" (en "La solución original solo tiene datos en el escenario aleatorio.")

**Comentario:** "faltaría un párrafo con información de la plataforma en que se hicieron las corridas y se midieron los tiempos"

**Qué se hizo:** se agregó un párrafo "Plataforma" (equipo, contenedores, versiones de PHP y GLPK, qué incluye el tiempo medido). Además se corrigió el número de corridas en N = 150, que no coincidía con los datos.

## Sugerencias de edición del 18 de octubre de 2026

Las sugerencias hechas directamente sobre el texto se incorporaron todas. Se transcriben, junto con los comentarios hechos sobre ellas, porque al volver a subir el documento desaparecen.

### #27 · Resumen

**Sugerencia:** reemplazar "requiere personal técnico o al menos con habilidades suficientes para configurar el entorno, instalar la aplicación…" por "requiere la participación de personas con formación profesional o técnica en informática o al menos con habilidades suficientes para instalar la aplicación…".

**Comentario sobre la sugerencia:** "el instalador que está ahora ya configura el entorno, creo que se puede dejar así."

**Qué se hizo:** incorporada.

### #28 · Parte I, presentación

**Nota en el texto:** "(falta un párrafo inicial de descripción de esta parte del informe; en las otras Partes está ese páarrafo)"

**Qué se hizo:** se agregó el párrafo inicial de la Parte I, con el mismo formato que en las demás Partes.

### #29 · Capítulos y secciones

**Sugerencia:** en cuatro lugares, "la Sección 2", "la Sección 7", "la Sección 8" y "Esta sección cierra la Parte I" pasan a "el Capítulo…" y "Este capítulo…".

**Comentario sobre la sugerencia:** "Hay que elegir si son capítulos o secciones, me pareció que te habías decantado más por capítulos, hay que dejar homogéneo el documento en este sentido"

**Qué se hizo:** incorporada, y aplicada en todo el documento: las referencias a unidades numeradas de primer nivel dicen ahora "Capítulo" (veintiocho lugares, marcados en amarillo); "sección" queda para las subdivisiones (por ejemplo, la Sección 2.3).

### #30 · 3.2 Implicancias más amplias

**Sugerencia:** "tiene las siguientes consecuencias que van más allá de la comodidad:".

**Qué se hizo:** incorporada.

### #31 · 6. Apertura del capítulo

**Sugerencia:** agregar al comienzo "En este capítulo se describe el conjunto de etapas en el proceso de asignación de las viviendas de un proyecto cooperativo."

**Qué se hizo:** incorporada en la reescritura del párrafo (véase #20), adaptada al nuevo título del capítulo (véase #19).

### #32 · Formato de los capítulos

**Comentario:** "En lo que refiere a formato visual, es más común que un documento empezado en capítulos, cada capítulo comience en una página nueva.  Igual, como son muy cortos, no recomendaría hacer esto en este documento."

**Qué se hizo:** sin cambios, como recomienda el comentario.
