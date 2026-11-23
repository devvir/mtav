```{=openxml}
<w:p><w:r><w:br w:type="page"/></w:r></w:p>
```

# Conclusiones y trabajo futuro {#cap-conclusiones}

Este capítulo cierra el trabajo: evalúa lo logrado frente a lo planteado (§@sec-conclusiones) y organiza las líneas de trabajo futuro por área temática (§@sec-trabajo-futuro).

## Conclusiones {#sec-conclusiones}

Este trabajo se propuso llevar un método de asignación probado —el algoritmo de MTAV, validado en proyectos cooperativos reales— desde una herramienta de escritorio que requería asistencia técnica hasta una plataforma de autogestión al alcance de cualquier cooperativa. Ese objetivo, planteado explícitamente como trabajo futuro en la investigación previa sobre MTAV (el "MTAV Online" de Fierro, 2024), se cumplió: MTAV en línea existe, funciona, y cubre el ciclo completo que esta tesis describe —desde la creación del proyecto y la incorporación de las familias hasta la ejecución auditada del sorteo y la publicación de su resultado—.

De lo planteado a lo construido, el balance es el siguiente:

- **La plataforma.** Una aplicación web y móvil completa, con tres roles [y acceso por invitación]{.quitado}[, en la que cualquier cooperativa crea su proyecto y suma a su gente por invitación o desde un directorio]{.nuevo}. Las familias ordenan sus preferencias en privado, con ajuste dinámico, y el proyecto dispone de un plano interactivo, eventos, medios compartidos, [un foro, un chat y un Asistente,]{.nuevo} notificaciones en tiempo real [y en los dispositivos]{.nuevo}, [dos]{.quitado}[cinco]{.nuevo} idiomas[, lectura sin conexión]{.nuevo} y accesibilidad como requisito de primer orden (Capítulos @cap-funcional y @cap-ingenieria). [Un *pipeline* prueba cada cambio y lo despliega en la nube sin que nadie entre a un servidor (§@sec-entrega).]{.nuevo} La operación que antes exigía un intermediario técnico —instalar, recolectar planillas, consolidar a mano, ejecutar, comunicar— hoy es un flujo de autogestión de principio a fin. [Nov. 2026: lo que agregó la reescritura]{.rev #rev-n-balance}
- **El aporte algorítmico.** La Fase 1 del método original, su único punto de fragilidad, fue reemplazada por una búsqueda binaria sobre la cota de equidad, con equivalencia demostrada formalmente y verificada empíricamente contra la implementación original (Capítulo @cap-sorteo). El resultado práctico está cuantificado: instancias que antes expiraban o no terminaban —incluidos los escenarios degenerados— hoy se resuelven al 100 %, sin un solo caso fallido hasta tamaños un orden de magnitud mayores que cualquier proyecto real (§@sec-resultados, Apéndice C.3).
- **La verificabilidad.** Cada ejecución del sorteo deja una traza de auditoría reproducible —entrada congelada, artefactos del *solver*, resultado— [y un acta con su huella, que cualquier socio lee,]{.nuevo} que convierte la confianza en el proceso en una propiedad verificable y no en un acto de fe (§@sec-auditoria, Apéndice C).
- **La calidad como proceso.** El sistema se construyó con una suite de pruebas en capas, puertas de calidad automatizadas y documentación de usuario por rol integrada en la propia aplicación (Apéndices E y H).
- [**La experiencia de desarrollo como prioridad.** Una plataforma pensada para durar más que su primer autor tiene que poder retomarse. Por eso la experiencia de desarrollo recibió un papel de primer orden, y no el de un accesorio: un solo comando levanta el entorno completo desde un clon limpio, los entornos de desarrollo, pruebas y navegador conviven sin tocarse, la suite entera corre en cada *push* y cada versión llega a los servidores sin que nadie entre a ellos (§@sec-arquitectura, Apéndice I). El beneficiario inmediato fue el propio autor, que operó el proyecto con menos fricción y menos errores; el beneficiario buscado es quien lo retome —un colaborador de la Facultad, una cooperativa que quiera alojarlo—, que debería poder empezar a trabajar en minutos y con confianza.]{.nuevo} [Nov. 2026: la experiencia de desarrollo como decisión del trabajo]{.rev #rev-n-dx}

Corresponde también señalar lo que este trabajo **no** alcanzó a hacer, y que define el paso inmediato: la plataforma **no está aún [desplegada en producción ni]{.quitado} validada con cooperativas reales**. [Nov. 2026]{.rev #rev-n-pendiente} El algoritmo hereda la validación de campo del MTAV original, pero la plataforma que lo rodea —la experiencia de autogestión que constituye la promesa central de este trabajo— debe todavía probarse con usuarios reales, en un sorteo real. Esa validación es la primera prioridad de la etapa siguiente.

## Trabajo futuro {#sec-trabajo-futuro}

Varias líneas de trabajo quedan fuera del alcance de esta tesis pero mejorarían la utilidad o la calidad de la plataforma. Se organizan por área temática.

### Comunicación y comunidad {#sec-tf-comunicacion}

Queda pendiente un sistema de anuncios enriquecidos con adjuntos, y la integración con medios externos (por ejemplo, notificaciones por WhatsApp o correo masivo). Es la continuación natural del foro y el chat (§@sec-foro-chat) y de la naturaleza compartida descrita en el Capítulo @cap-intro. [Nov. 2026]{.rev #rev-n-tf-comunicacion}

### El plano espacial {#sec-tf-plano}

Falta una **escala real**, para que quien dibuja desde un croquis ubique las cosas en metros y no en las unidades del lienzo, con la representación de superficies reales en metros cuadrados. Quedan también pendientes la exportación del plano a imagen o PDF y la importación de planos arquitectónicos existentes. [Nov. 2026]{.rev #rev-n-tf-plano}

### Rendimiento y escalabilidad {#sec-tf-rendimiento}

Quedan por explorar una capa de caché para las consultas frecuentes y la optimización del *solver* para proyectos de gran escala. Las listas de hasta doscientos elementos —casi todas las de un proyecto— se buscan y ordenan en el navegador (§@sec-arquitectura); el paso siguiente es dibujar cada página desde los datos guardados en el dispositivo antes de que el servidor responda, lo que reconfigura cada página y debe medirse antes de hacerse. [Nov. 2026]{.rev #rev-n-tf-rendimiento}

### Integración con sistemas externos {#sec-tf-integracion}

Quedan pendientes la exportación de los resultados del sorteo en formatos legales o notariales, la integración con registros públicos de cooperativas y las API para sistemas de gestión de obra o de administración cooperativa.

### Analítica, reportes y transparencia {#sec-tf-analitica}

Quedan pendientes un histórico de proyectos con comparación entre sorteos; **estadísticas de satisfacción agregadas** —métricas que reflejen qué tan bueno fue el resultado alcanzado, más allá del peor lugar y el promedio que muestra el acta de cada ejecución (§@sec-auditoria-funcional)—; y reportes exportables para asambleas o auditorías externas. En esta línea, una mejora concreta de transparencia es permitir que, una vez ejecutado el sorteo, el administrador **habilite la visualización de las preferencias de todas las familias** para el conjunto de los socios, de modo que cualquiera pueda verificar el resultado contra las preferencias ingresadas (hoy cada familia ve solo las propias). [Nov. 2026]{.rev #rev-n-tf-analitica}

### Mejoras al algoritmo y al proceso de asignación {#sec-tf-algoritmo}

El trabajo previo sobre MTAV dejó planteadas varias extensiones al algoritmo, exploradas en el proyecto de grado de Marcos Fierro (2024): un **criterio adicional de equidad** basado en la desviación estándar de las satisfacciones, con la posibilidad de elegir sobre un frente de Pareto entre mayor equidad y mayor satisfacción global; la incorporación de **preferencias de vecindad** (que una familia valore quedar cerca de otra determinada); y las **preferencias "en bloque"** (igualar la prioridad de varias unidades indiferentes). A ellas se suman otras posibilidades: restricciones adicionales —por ejemplo, reservar unidades en planta baja para familias con necesidades de accesibilidad—, la ponderación de las preferencias por intensidad (no solo por orden), y la ejecución de sorteos parciales o por etapas. Estas mejoras se relacionan directamente con el Capítulo @cap-sorteo.

### Experiencia de usuario y accesibilidad avanzada {#sec-tf-ux}

Queda pendiente el soporte completo de lectores de pantalla, y que los cambios hechos sin conexión se guarden en el dispositivo y se envíen al recuperarla, en lugar de retenerse (§@sec-implicancias-mobile). [Nov. 2026]{.rev #rev-n-tf-ux}

