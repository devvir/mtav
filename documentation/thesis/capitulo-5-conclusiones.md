```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Conclusiones y trabajo futuro {#cap-conclusiones}

Este capítulo cierra el trabajo: evalúa lo logrado frente a lo planteado (§@sec-conclusiones) y organiza las líneas de trabajo futuro por área temática (§@sec-trabajo-futuro).

## Conclusiones {#sec-conclusiones}

Este trabajo se propuso llevar un método de asignación probado —el algoritmo de MTAV, validado en proyectos cooperativos reales— desde una herramienta de escritorio que requería asistencia técnica hasta una plataforma de autogestión al alcance de cualquier cooperativa. Ese objetivo, planteado explícitamente como trabajo futuro en la investigación previa sobre MTAV (el "MTAV Online" de Fierro, 2024), se cumplió: MTAV en línea existe, funciona, y cubre el ciclo completo que esta tesis describe —desde la creación del proyecto y la incorporación de las familias hasta la ejecución auditada del sorteo y la publicación de su resultado.

De lo planteado a lo construido, el balance es el siguiente:

- **La plataforma.** Una aplicación web y móvil completa, con tres roles [y acceso por invitación]{.quitado}[por proyecto, en la que cualquier cooperativa crea su proyecto y suma a su gente por invitación o desde un directorio]{.nuevo}, preferencias familiares privadas con ajuste dinámico, plano interactivo, eventos, medios compartidos, [un foro, un chat y un Asistente, ]{.nuevo}notificaciones en tiempo real[ y en los dispositivos]{.nuevo}, [dos]{.quitado}[cinco]{.nuevo} idiomas, [lectura sin conexión, ]{.nuevo}y accesibilidad como requisito de primer orden (Capítulos @cap-funcional y @cap-ingenieria)[; entregada por un pipeline que prueba cada cambio y lo despliega en la nube sin que nadie entre a un servidor (§@sec-entrega)]{.nuevo}. La operación que antes exigía un intermediario técnico —instalar, recolectar planillas, consolidar a mano, ejecutar, comunicar— hoy es un flujo de autogestión de principio a fin. [Nov. 2026: lo que agregó la reescritura]{.rev #rev-n-balance}
- **El aporte algorítmico.** La Fase 1 del método original, su único punto de fragilidad, fue reemplazada por una búsqueda binaria sobre la cota de equidad, con equivalencia demostrada formalmente y verificada empíricamente contra la implementación original (Capítulo @cap-sorteo). El resultado práctico está cuantificado: instancias que antes expiraban o no terminaban —incluidos los escenarios degenerados— hoy se resuelven al 100 %, sin un solo caso fallido hasta tamaños un orden de magnitud mayores que cualquier proyecto real (§@sec-resultados, Apéndice C.3).
- **La verificabilidad.** Cada ejecución del sorteo deja una traza de auditoría reproducible —entrada congelada, artefactos del solver, resultado—[ y un acta con su huella, que cualquier socio lee,]{.nuevo} que convierte la confianza en el proceso en una propiedad verificable y no en un acto de fe (§@sec-auditoria, Apéndice C).
- **La calidad como proceso.** El sistema se construyó con una suite de pruebas en capas, puertas de calidad automatizadas y documentación de usuario por rol integrada en la propia aplicación (Apéndices E y H).

Corresponde también señalar lo que este trabajo **no** alcanzó a hacer, y que define el paso inmediato: la plataforma **no está aún [desplegada en producción ni ]{.quitado}[abierta al público ni ]{.nuevo}validada con cooperativas reales**. [La infraestructura está construida y en uso —los entornos de desarrollo y de prueba y la variante Open Lottery corren en la nube—, pero el servidor de producción permanece apagado hasta el lanzamiento (§@sec-entrega).]{.nuevo} [Nov. 2026: estado del despliegue]{.rev #rev-n-pendiente} El algoritmo hereda la validación de campo del MTAV original, pero la plataforma que lo rodea —la experiencia de autogestión que constituye la promesa central de este trabajo— debe todavía probarse con usuarios reales, en un sorteo real. Esa validación es la primera prioridad de la etapa siguiente.

[NOTA: autocrítica y dificultades encontradas — la guía del informe pide evaluar qué se planteó vs. qué se hizo, dificultades, y autocrítica de lo que faltó (tiempo, recursos, prioridades). Este es territorio de Diego: ¿qué fue más difícil de lo esperado? ¿qué harías distinto? ¿qué quedó afuera por tiempo?]

[NOTA: gestión del proyecto ("si aplica", dice la guía) — metodología de trabajo, etapas, uso de IA (remite al Apéndice F si alcanza con eso). Decidir si se incluye un párrafo acá.]

## Trabajo futuro {#sec-trabajo-futuro}

Varias líneas de trabajo quedan fuera del alcance de esta tesis pero mejorarían la utilidad o la calidad de la plataforma. Se organizan por área temática.

### Comunicación y comunidad {#sec-tf-comunicacion}

[Un canal de mensajería o foro interno para que los cooperativistas se comuniquen dentro del proyecto —hoy esa comunicación ocurre en herramientas externas—, un sistema]{.quitado}[El foro y el chat (§@sec-foro-chat) cubren hoy la comunicación interna; quedan un sistema]{.nuevo} de anuncios enriquecidos con adjuntos, y la integración con medios externos (por ejemplo, notificaciones por WhatsApp o correo masivo). Es la continuación natural de la naturaleza compartida descrita en el Capítulo @cap-intro. [Nov. 2026: foro y chat construidos]{.rev #rev-n-tf-comunicacion}

### El plano espacial {#sec-tf-plano}

[Completar y enriquecer el editor de plano: **redimensionar y reformar** unidades (mover los vértices de los polígonos), **soporte multinivel** para proyectos de varios pisos (navegación entre plantas, ya contemplado a nivel de datos), representación]{.quitado}[El editor de plano se rehízo (§@sec-plano): las formas se mueven, redimensionan y giran, y el plano tiene todos los pisos que hagan falta. Quedan la]{.nuevo} representación de superficies reales en metros cuadrados[ —una escala real, para que quien dibuja desde un croquis ubique las cosas en metros y no en las unidades del lienzo—]{.nuevo}, exportación del plano a imagen o PDF, e importación de planos arquitectónicos existentes. [Nov. 2026: editor rehecho]{.rev #rev-n-tf-plano}

### Rendimiento y escalabilidad {#sec-tf-rendimiento}

Una capa de caché para consultas frecuentes[,]{.quitado}[ y]{.nuevo} optimización del solver para proyectos de gran escala[, y paginación o virtualización en listas largas de unidades o familias]{.quitado}. [Las listas de un proyecto, que rara vez superan los doscientos elementos, ya se buscan y ordenan en el navegador (§@sec-implicancias-mobile); el paso siguiente es dibujar cada página desde los datos guardados en el dispositivo antes de que el servidor responda, lo que reconfigura cada página y debe medirse antes de hacerse.]{.nuevo} [Nov. 2026]{.rev #rev-n-tf-rendimiento}

### Integración con sistemas externos {#sec-tf-integracion}

Exportación de los resultados del sorteo en formatos legales o notariales, integración con registros públicos de cooperativas, y APIs para sistemas de gestión de obra o de administración cooperativa.

### Analítica, reportes y transparencia {#sec-tf-analitica}

Histórico de proyectos y comparación entre sorteos; **estadísticas de satisfacción agregadas** —métricas que reflejen qué tan bueno fue el resultado alcanzado[, hoy inexistentes]{.quitado}[; hoy el acta de cada ejecución muestra el peor lugar y el promedio (§@sec-ejecucion-funcional), y nada más]{.nuevo}—; y reportes exportables para asambleas o auditorías externas. En esta línea, una mejora concreta de transparencia es permitir que, una vez ejecutado el sorteo, el administrador **habilite la visualización de las preferencias de todas las familias** para el conjunto de los socios, de modo que cualquiera pueda verificar el resultado contra las preferencias ingresadas (hoy cada familia ve solo las propias). [Nov. 2026]{.rev #rev-n-tf-analitica}

### Mejoras al algoritmo y al proceso de asignación {#sec-tf-algoritmo}

El trabajo previo sobre MTAV dejó planteadas varias extensiones al algoritmo, exploradas en el proyecto de grado de Marcos Fierro (2024): un **criterio adicional de equidad** basado en la desviación estándar de las satisfacciones, con la posibilidad de elegir sobre un frente de Pareto entre mayor equidad y mayor satisfacción global; la incorporación de **preferencias de vecindad** (que una familia valore quedar cerca de otra determinada); y las **preferencias "en bloque"** (igualar la prioridad de varias unidades indiferentes). A ellas se suman otras posibilidades: restricciones adicionales —por ejemplo, reservar unidades en planta baja para familias con necesidades de accesibilidad—, la ponderación de las preferencias por intensidad (no solo por orden), y la ejecución de sorteos parciales o por etapas. Estas mejoras se relacionan directamente con el Capítulo @cap-sorteo.

[NOTA: la revisión del código de noviembre de 2026 dejó dos hallazgos sobre la equidad del solver, pendientes de diseño (desempate que se redondea; max-min perdido en tipos desbalanceados; véase la nota en §@sec-garantias). Decidir si se enuncian aquí como trabajo futuro.]

### Experiencia de usuario y accesibilidad avanzada {#sec-tf-ux}

Soporte completo de lectores de pantalla[ y un modo offline que permita navegar la aplicación y consultar la información ya cargada —el plano, los eventos, las preferencias propias— sin conexión, útil en zonas con conectividad limitada]{.quitado}[. La lectura sin conexión ya existe (§@sec-implicancias-mobile); queda que los cambios hechos sin conexión se guarden y se envíen al recuperarla, en lugar de retenerse]{.nuevo}. [Nov. 2026: modo sin conexión construido en lectura]{.rev #rev-n-tf-ux}

