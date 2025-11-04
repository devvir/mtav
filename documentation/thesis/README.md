# Tesis: MTAV - Mejor Tecnología de Asignación de Viviendas

**Autor:** [Tu Nombre]
**Carrera:** Ingeniería en Computación
**Institución:** [Tu Universidad]
**Fecha:** Noviembre 2025

---

## Estructura del Documento

Esta tesis está organizada en múltiples archivos para facilitar la lectura y mantenimiento:

### Archivo Principal
- **`TESIS.md`** - Documento principal que incluye:
  - Resumen / Abstract
  - Introducción y organización del documento
  - **PARTE 1: Visión General (Secciones 1-5)**
    - Sección 1: El Problema
    - Sección 2: Estado del Arte
    - Sección 3: La Aplicación MTAV
    - Sección 4: El Stack Tecnológico
    - Sección 5: Asistencia de Inteligencia Artificial

### Análisis Técnico Detallado
- **`PART_2_TECHNICAL_DEEP_DIVE.md`** - Secciones técnicas 6-8:
  - Sección 6: Docker - Infraestructura de Contenedores
  - Sección 7: Laravel, Vue.js e Inertia.js
  - Sección 8: Testing con Pest PHP

- **`PART_2_TECHNICAL_DEEP_DIVE_CONTINUED.md`** - Secciones técnicas 9-14:
  - Sección 9: Desarrollo Asistido por IA - Metodología Detallada
  - Sección 10: Seguridad - Control de Acceso y Protección
  - Sección 11: Auditabilidad y Trazabilidad
  - Sección 12: Flexibilidad y Extensibilidad
  - Sección 13: Legibilidad - Código como Prosa
  - Sección 14: Localización - Soporte Multiidioma

### Apéndices
- **`appendices/APPENDICES.md`** - Material complementario:
  - Apéndice A: Fragmentos de Código Representativos
  - Apéndice B: Documentación Técnica
  - Apéndice C: Ejemplos de Diálogos con IA
  - Apéndice D: Capturas de Pantalla (placeholders)
  - Apéndice E: Referencias a Guías de Usuario
  - Apéndice F: Preguntas Frecuentes (FAQ)

---

## Cómo Leer Este Documento

### Para Evaluadores Generales
1. Leer **Resumen** en `TESIS.md`
2. Leer **Introducción** para entender la organización
3. Leer **Secciones 1-5** (visión general accesible)
4. Opcionalmente consultar **Apéndices E y F** para casos de uso

### Para Evaluadores Técnicos
1. Leer **Resumen** e **Introducción** en `TESIS.md`
2. Leer **Secciones 1-5** para contexto
3. Profundizar en **Secciones 6-14** (análisis técnico detallado)
4. Revisar **Apéndice A** (código representativo)
5. Consultar **Apéndice B** (documentación técnica)

### Para Desarrolladores Futuros
1. Comenzar con **Sección 4** (Stack Tecnológico)
2. Leer **Sección 6** (Docker) para configurar entorno
3. Leer **Secciones 7-8** (Laravel/Vue, Testing)
4. Consultar **Apéndice B** como referencia rápida
5. Usar **Apéndice A** para entender patrones de código

---

## Conversión a PDF

Para generar un PDF único a partir de estos archivos Markdown:

### Opción 1: Pandoc (Recomendado)

```bash
# Instalar Pandoc
sudo apt-get install pandoc texlive-latex-base texlive-fonts-recommended

# Generar PDF
pandoc TESIS.md \
       PART_2_TECHNICAL_DEEP_DIVE.md \
       PART_2_TECHNICAL_DEEP_DIVE_CONTINUED.md \
       appendices/APPENDICES.md \
       -o MTAV_Tesis.pdf \
       --toc \
       --toc-depth=3 \
       --number-sections \
       -V geometry:margin=2.5cm \
       -V fontsize=12pt \
       -V documentclass=report \
       -V lang=es-UY
```

### Opción 2: Markdown to PDF (VS Code)

1. Instalar extensión "Markdown PDF" en VS Code
2. Abrir `TESIS.md`
3. Presionar `Ctrl+Shift+P` → "Markdown PDF: Export (pdf)"
4. Repetir para cada archivo
5. Combinar PDFs con herramienta externa

### Opción 3: Herramientas Online

- **Dillinger.io:** Importar markdown, exportar PDF
- **StackEdit:** Editor markdown con export a PDF
- **Markdown to PDF:** https://www.markdowntopdf.com/

---

## Configuración de Estilo (para Pandoc)

Si deseas personalizar el estilo del PDF, crea un archivo `template.yaml`:

```yaml
---
title: "MTAV - Mejor Tecnología de Asignación de Viviendas"
subtitle: "Sistema de Gestión para Cooperativas de Vivienda"
author: "[Tu Nombre]"
date: "Noviembre 2025"
lang: es-UY
fontsize: 12pt
geometry: margin=2.5cm
documentclass: report
toc: true
toc-depth: 3
number-sections: true
colorlinks: true
linkcolor: blue
urlcolor: blue
header-includes: |
    \usepackage{fancyhdr}
    \pagestyle{fancy}
    \fancyhead[L]{MTAV - Tesis}
    \fancyhead[R]{\thepage}
    \fancyfoot[C]{Universidad [Nombre] - Ingeniería en Computación}
---
```

Luego usar:

```bash
pandoc --metadata-file=template.yaml \
       TESIS.md \
       PART_2_TECHNICAL_DEEP_DIVE.md \
       PART_2_TECHNICAL_DEEP_DIVE_CONTINUED.md \
       appendices/APPENDICES.md \
       -o MTAV_Tesis.pdf
```

---

## Estadísticas del Documento

**Estimación de páginas (formato PDF estándar):**

- TESIS.md (Secciones 1-5): ~25-30 páginas
- PART_2_TECHNICAL_DEEP_DIVE.md (Secciones 6-8): ~35-40 páginas
- PART_2_TECHNICAL_DEEP_DIVE_CONTINUED.md (Secciones 9-14): ~35-40 páginas
- APPENDICES.md: ~20-25 páginas

**Total estimado: 115-135 páginas**

**Contenido:**
- ~60,000 palabras
- ~400,000 caracteres
- 14 secciones principales
- 6 apéndices
- Múltiples ejemplos de código
- Diagramas y explicaciones técnicas

---

## Notas para Revisión

### Primera Revisión (Recomendada)
- [ ] Verificar que todos los nombres propios estén correctos
- [ ] Añadir nombre del autor en todos los archivos
- [ ] Añadir nombre de la universidad
- [ ] Revisar fechas (actualmente Nov 2025)
- [ ] Añadir capturas de pantalla reales en Apéndice D
- [ ] Verificar que todos los fragmentos de código compilen/funcionen

### Segunda Revisión (Opcional)
- [ ] Añadir bibliografía/referencias si es requerido
- [ ] Incluir más diagramas arquitectónicos
- [ ] Expandir sección de resultados/métricas si se requiere
- [ ] Añadir sección de conclusiones finales
- [ ] Añadir sección de trabajo futuro

---

## Mantenimiento

Este documento es la **versión borrador inicial** generada el 5 de noviembre de 2025.

**Próximos pasos:**

1. **Revisión de contenido:** Leer completo y verificar coherencia
2. **Añadir material faltante:** Capturas de pantalla, diagramas adicionales
3. **Pulir redacción:** Corregir errores gramaticales o de estilo
4. **Validar código:** Asegurar que todos los ejemplos sean precisos
5. **Generar PDF final:** Una vez aprobado el contenido
6. **Imprimir y empastar:** Para presentación física

---

## Contacto y Soporte

Para consultas sobre este documento o el proyecto MTAV:

- **Repositorio:** https://github.com/devvir/mtav
- **Documentación técnica:** `documentation/` en el repositorio
- **Knowledge Base:** `documentation/ai/KNOWLEDGE_BASE.md`

---

## Licencia

Este documento forma parte de un proyecto final de carrera universitaria.

Todos los derechos reservados © 2025 [Tu Nombre]

El código fuente del proyecto MTAV está licenciado bajo [especificar licencia].

---

**Última actualización:** 5 de noviembre de 2025

