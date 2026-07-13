---
lang: es
toc-title: "Índice de contenidos"
---

# Resumen

Las cooperativas de vivienda constituyen una parte fundamental de la política habitacional uruguaya desde hace más de cincuenta años. Al finalizar la construcción de un proyecto cooperativo, las familias socias deben recibir cada una exactamente una vivienda, en un proceso que es a la vez legalmente sensible y socialmente delicado: las preferencias de las familias entran en conflicto, y el resultado debe ser percibido como justo, transparente y no negociable.

Para resolver este problema, la Facultad de Ingeniería de la Universidad de la República desarrolló un enfoque basado en programación lineal entera que, utilizando el solver de código abierto GLPK (GNU Linear Programming Kit), garantiza una asignación matemáticamente óptima y justa. El algoritmo opera en dos fases: primero maximiza el mínimo de satisfacción entre todas las familias —garantizando equidad— y luego maximiza la satisfacción global bajo esa restricción. Este método fue aplicado con éxito en más de veinte proyectos cooperativos reales en Uruguay.

Sin embargo, su operativa presenta una limitación importante: requiere personal técnico o al menos con habilidades suficientes para configurar el entorno, instalar la aplicación, cargar los datos y ejecutar la optimización. En cada proyecto, las cooperativas dependen de ese intermediario (o de contar entre sus integrantes con alguna persona con las habilidades requeridas).

MTAV en línea es una aplicación web y móvil que elimina esa dependencia. Implementando el mismo algoritmo base en una plataforma de autogestión, permite que cualquier cooperativa administre su propio proceso de asignación de principio a fin, sin necesidad de asistencia técnica externa. Cualquier administrador del sistema puede registrar familias y ejecutar el sorteo, y los cooperativistas pueden registrar sus preferencias directamente.

Además de la plataforma en sí, este trabajo presenta una mejora original al algoritmo: la Fase 1 —históricamente resuelta mediante GLPK, con tiempos problemáticos en casos degenerados o con muchas familias y viviendas— es reemplazada por un algoritmo de búsqueda binaria que produce un resultado equivalente de forma eficiente. La Fase 2 continúa utilizando GLPK con los modelos originales.

El sistema incluye, entre otras funcionalidades: gestión de proyectos, familias y cooperativistas; recolección de preferencias mediante una interfaz de arrastrar y soltar; un plano visual interactivo del proyecto; gestión de eventos y medios; notificaciones en tiempo real; y un registro de auditoría completo de cada ejecución del sorteo.

La aplicación fue diseñada con accesibilidad como requisito de primer orden, pensada para perfiles de usuario que incluyen adultos mayores y personas con discapacidad, así como dispositivos de gama baja.

**Palabras clave:** Asignación de Viviendas, Cooperativas de Vivienda, Housing Allocation, Programación Lineal Entera, GLPK, Aplicación Web, Proyecto de Grado, Computación.
