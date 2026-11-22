# Cambios en esta versión {.unnumbered .unlisted}

Registro temporal de lo que cambió desde la revisión anterior; desaparece, junto con las marcas de color, en la versión de entrega. Cada número lleva al cambio en el texto.

[Texto agregado]{.nuevo} · [Texto reescrito]{.cambio} · [Texto eliminado]{.quitado} · [#15: motivo del cambio]{.rev}

## Estructura del documento

Según lo propuesto por correo, las partes pasaron a ser capítulos, los capítulos secciones y las secciones subsecciones; se renumeró el documento y se ajustó la redacción. Las conclusiones y el trabajo futuro forman un capítulo propio, el Capítulo @cap-conclusiones, y cada capítulo empieza en una página nueva (#32). Esto reemplaza la sugerencia #29 y no se marca en el texto. La sección sobre el *stack* tecnológico (antes la primera del Capítulo @cap-funcional) pasó a abrir el Capítulo @cap-ingenieria, de modo que el capítulo funcional describa solo comportamiento observable; la sección sobre principios y patrones de diseño cierra el Capítulo @cap-ingenieria, como síntesis de las secciones que la preceden.

## Revisión del 18 de octubre de 2026

- [#16](#rev-16) Resumen: aclaración breve de "no negociable".
- [#17](#rev-17) §@sec-proceso-asignacion: citas de Prino, Sánchez y Cancela (2016) y de Fierro (2024).
- [#18](#rev-18) §@sec-panorama: criterios generales de elección del stack.
- [#19](#rev-19) §@sec-ciclo: nuevo título, "El ciclo de vida de un proyecto en la plataforma": la sección recorre un proyecto dentro de la plataforma, no el proceso de asignación.
- [#20 y #31](#rev-20) §@sec-ciclo: nueva apertura; el orden es el de uso de la plataforma, se nombran las subsecciones y se retoma la frase sugerida.
- [#21](#rev-21) §@sec-sorteo-funcional: la apertura remite a sus subsecciones y a las secciones del Capítulo @cap-sorteo.
- [#22](#rev-22) §@sec-cuello-fase1: definición del escenario aleatorio; el equipo de las mediciones se describe una sola vez, en §@sec-resultados.
- [#23](#rev-23) §@sec-resultados: generación de las instancias, los cuatro escenarios y la plataforma. Al escribir cómo se generaba el escenario realista encontré que el generador no producía unidades “populares” comunes a las familias, que era su intención; se corrigió, y las cinco series (los cuatro escenarios y la solución original) se volvieron a medir sobre el código actual, en la misma máquina. Los números de §@sec-resultados, la figura y el Apéndice C.3 son todavía los de julio y cambian en la próxima versión.
- [#24 a #26](#rev-24) Apéndice C.3: generación reproducible de cada escenario (en el realista, una división popular/impopular común a todas las familias; en el opuesto, un único orden fijo que la mitad de las familias adopta invertido) y la plataforma de las corridas.
- [#27](#rev-27) Resumen: sugerencia de redacción incorporada.
- [#28](#rev-28) Capítulo @cap-intro: párrafo inicial, como en los demás capítulos.
- [#30](#rev-30) §@sec-implicancias: sugerencia de redacción incorporada.

## Actualización al sistema reescrito (noviembre de 2026)

Entre fines de octubre y noviembre de 2026 la aplicación se reescribió casi por entero: registro abierto y proyectos que cualquiera crea, roles por proyecto, frontend en React, foro, chat y Asistente, entrega continua en la nube. El documento se actualizó al sistema actual, sin tocar lo que no cambió. Cada sección nueva o reescrita lleva una nota azul al comienzo; dentro del texto que ya estaba, los cambios van en color como hasta ahora. Las secciones reescritas no se marcan frase por frase: se releen enteras.

- [Resumen](#rev-n-resumen): registro abierto, foro, chat, Asistente IA, cinco idiomas.
- Cambios de la aplicación de fines de noviembre, reflejados en el Capítulo @cap-funcional y en los Apéndices B, C y G: [los administradores son pares y pueden renunciar al rol](#rev-n-pares) (§@sec-familias); [el registro del sorteo se divulga a todo el proyecto pasado un plazo, con todos los rankings y el registro completo para descargar](#rev-n-divulgacion) (§@sec-ejecucion-funcional, §@sec-auditoria, C.2, RF-4, RNF-4); un ritmo de uso acotado y la configuración avanzada en los proyectos limitados (§@sec-configuracion).
- [Una pregunta para el tutor](#rev-q-proyectos), en la nota al pie de §@sec-validacion: la lista de cooperativas que usaron MTAV.
- Capítulo @cap-intro: [§@sec-implicancias](#rev-n-comunidad) (el canal interno ya existe); [§@sec-roles](#rev-n-roles) y [§@sec-ciclo-mtav](#rev-n-ciclo-mtav) reescritas.
- Capítulo @cap-funcional: [§@sec-configuracion](#rev-n-configuracion), [§@sec-familias](#rev-n-familias), [§@sec-plano](#rev-n-plano), [§@sec-eventos](#rev-n-eventos) (archivo), [§@sec-medios](#rev-n-medios) y [§@sec-notificaciones](#rev-n-notificaciones) reescritas; [§@sec-foro-chat](#rev-n-foro-chat) y [§@sec-ayuda](#rev-n-ayuda) nuevas; [§@sec-preferencias-funcional](#rev-n-preferencias), [§@sec-garantias-funcional](#rev-n-desempate) (los empates) y [§@sec-ejecucion-funcional](#rev-n-ejecucion-funcional) (ejecuciones, revelación, acta, invalidación por administradores); [§@sec-decisiones-accesibilidad](#rev-n-accesibilidad), [§@sec-i18n](#rev-n-i18n) y [§@sec-implicancias-mobile](#rev-n-mobile).
- Capítulo @cap-sorteo: [§@sec-ejecucion](#rev-n-orquestacion) (motor generalizado; [cómo se resuelve el ranking de cada familia](#rev-n-preferencias-3); [límites de la poda y de las unidades ficticias](#rev-n-poda); [el desempate, tal como está implementado](#rev-n-desempate-3)), [§@sec-bb-equivalencia](#rev-n-cuello-botella) (la relación con el problema de asignación de cuello de botella) y [§@sec-auditoria](#rev-n-auditoria) reescrita. La formulación y los resultados no cambiaron.
- Capítulo @cap-ingenieria: [§@sec-panorama](#rev-n-stack) (React, Mantine, versiones), [§@sec-arquitectura](#rev-n-arquitectura), [§@sec-capa-sorteo](#rev-n-capa-sorteo), [§@sec-autorizacion](#rev-n-autorizacion) y [§@sec-recursos](#rev-n-recursos) reescritas; [§@sec-patrones](#rev-n-patrones), [§@sec-variantes](#rev-n-variantes), [§@sec-asistente](#rev-n-asistente), [§@sec-pruebas](#rev-n-pruebas), [§@sec-entrega](#rev-n-entrega) y [§@sec-registros](#rev-n-registros) nuevas.
- Capítulo @cap-conclusiones: [el balance](#rev-n-balance), [la experiencia de desarrollo como decisión](#rev-n-dx) y [lo pendiente](#rev-n-pendiente); el trabajo futuro ([correcciones del solver](#rev-n-tf-solver), [comunicación](#rev-n-tf-comunicacion), [plano](#rev-n-tf-plano), [rendimiento](#rev-n-tf-rendimiento), [analítica](#rev-n-tf-analitica), [experiencia de usuario](#rev-n-tf-ux)).
- Pasada final de corrección, sin nota propia: cada tema queda desarrollado en un solo lugar y referido desde los demás (el ciclo de vida en el Capítulo @cap-funcional, el mecanismo de la ejecución en §@sec-auditoria, los proyectos limitados en §@sec-configuracion, la autorización en §@sec-autorizacion); terminología unificada (tipo de vivienda, rango, corridas, subsorteo, participante sin familia, *staging*), tipografía y estilo de las referencias; las afirmaciones que el código contradecía se corrigieron, en color donde el texto ya estaba, y la muestra del Apéndice C.2 se regeneró a partir de una ejecución real.
- Apéndices: A ([derivación](#rev-n-a1), [esquema](#rev-n-a2), [A.3](#rev-n-a3), [A.4](#rev-n-a4), [A.5](#rev-n-a5)), B ([B.1](#rev-n-b1), [B.2](#rev-n-b2)), C ([nombres de los conjuntos en C.1](#rev-n-c1), [C.2](#rev-n-c2)), D ([D.1](#rev-n-d1), [D.2](#rev-n-d2)), E ([E.1](#rev-n-e1), [E.2](#rev-n-e2)), F ([F.2](#rev-n-f2) acotada a la primera versión; [F.7, nueva](#rev-n-f7)), G ([actores](#rev-n-g1), [casos](#rev-n-g3), [requisitos](#rev-n-g4), [secuencia](#rev-n-g5)), [H](#rev-n-h) (enlaces a las guías, en lugar de transcribirlas), I ([I.1](#rev-n-i1), [I.3](#rev-n-i3)). Los tres diagramas se regeneraron.
