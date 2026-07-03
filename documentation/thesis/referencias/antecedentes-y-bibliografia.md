# El MTAV original — hechos citables y bibliografía

> 🟢 **CONFIABLE.** Extraído de la tesis de grado de Marcos Fierro (2024), fuente académica revisada
> por el mismo tutor de Diego (Héctor Cancela). PDF en `referencias/Fierro-2024-tesis-grado.pdf`
> (licencia CC BY 4.0 — se puede citar/reproducir con atribución). Alimenta §2, §3, §7.2, §15, Apéndice H.
> Donde se afirme un hecho del MTAV original, **citar la fuente correspondiente**, no inventar.

## Qué es el MTAV original (para §2.1)

- Herramienta para asignar viviendas en cooperativas a partir de las **preferencias** de los
  cooperativistas, mediante **programación lineal entera (MIP)**, resuelta con **GLPK** (GLPSOL v4.65).
- **Autoría: NO atribuir a una persona.** Fierro la describe como *"desarrollada por un equipo de
  docentes, estudiantes y egresados de la Facultad de Ingeniería de la República, con participación
  también de cooperativistas"*. Es un **esfuerzo colectivo y evolutivo** (varias ediciones desde 2016).
- Se **distribuye como aplicación de escritorio stand-alone** (instalable público + manual de usuario);
  el instalable incluye GLPK y demás componentes. Nuevas cooperativas reciben ayuda del "equipo de MTAV"
  y de cooperativistas voluntarios. (Coincide con el código Qt/C++ en `../../../../programa/`.)
- Uso real: **"muy bien valorado por numerosas cooperativas"** (Fierro no da un número exacto; Diego
  dice "varios/several"). **No afirmar "más de 20"** sin evidencia — los docs haiku lo dicen, Fierro no.
- **MTAV = "Mejor Tecnología de Asignación de Viviendas"** (confirmado por Diego como hecho).
  Fierro no lo expande en su tesis, pero el significado es este.

## El algoritmo de dos etapas (para §2, §7.2, §15) — modelos GMPL reales

Optimización **lexicográfica en dos etapas** (Prino et al. 2016). Asume **#familias = #viviendas** (N=H);
para viviendas vacías se agrega un **socio ficticio** con preferencias uniformes (indiferente).

- **Etapa 1 (cota Z / equidad max-min):** minimizar `Z` tal que la prioridad asignada a *cada* familia
  sea ≤ Z. Es decir, minimizar la **peor** prioridad recibida por cualquier familia (equidad max-min).
- **Etapa 2 (satisfacción global):** sujeta a mantener la cota Z, minimizar la **suma** de prioridades
  asignadas (mejor satisfacción agregada).
- Modelos GMPL exactos: Listing 2.1 (cota Z) y Listing 2.2 (satisfacción global) en Fierro 2024, §2.3.
  Variables binarias `asig[n,v]`, restricciones de asignación única por familia y por vivienda.
- MTAV **aleatoriza el desempate** entre soluciones equivalentes → *equal treatment of equals*.

## El cuello de botella de rendimiento (para §15.5 — clave para la contribución de Diego)

- Fierro 2024, §2.3 (cita directa, parafrasear): la resolución *"no suele tardar más de unos pocos
  segundos... **sin embargo han surgido algunas ocasiones donde es necesario colocar un límite de
  tiempo a la primera etapa (Z) para evitar que el sistema se estanque**"*.
- El comando real usaba **`--tmlim 75`** (límite de 75 s), confirmando el problema de estancamiento
  de la Etapa 1 en casos degenerados. **Esta es la motivación citable de la búsqueda binaria de Diego.**

## Propiedades económicas del MTAV (para §1.3, §7.2) — Paleo 2021

Tesis de maestría de **Joaquín Paleo Arrarte (2021, FCEA-Udelar)**, análisis desde diseño de mercados:
- ✅ **Eficiente ex-post** (Pareto-óptimo): no existe otra asignación que mejore a una familia sin
  perjudicar a otra.
- ❌ **No siempre strategy-proof**: existen casos en que un participante con información total de las
  preferencias ajenas puede manipular sus entradas para beneficiarse. *(Mitigado en la versión web:
  ingreso privado por usuario/contraseña → nadie ve las preferencias ajenas. Punto fuerte para §3/§12.)*
- ✅ **Equal treatment of equals** (equidad ex-ante): dos participantes con idénticas preferencias
  tienen igual probabilidad ante empates (por la aleatorización).

## "MTAV Online" — Fierro propuso EXPLÍCITAMENTE la web (para §3, motivación)

Fierro 2024, §4.2, subsección **"MTAV Online"**: propone como trabajo futuro llevar MTAV a la web.
Ventajas que enumera (= propuesta de valor de la tesis de Diego):
- Acceso por navegador → sin dependencias técnicas del dispositivo; **sin paso de instalación**.
- Uso desde **móviles** (dispositivo más usado hoy).
- Generación de **plantillas de preferencias** (hoy hechas a mano en Excel/Sheets).
- Mayor difusión de MTAV como tecnología libre de la FING; acceso a manuales, contactos, logs.
- **Ingreso privado de preferencias** (usuario/contraseña) → anonimato, y **mitiga la vulnerabilidad
  de strategy-proofness** que señaló Paleo, dando más confianza.
> Narrativa: **la tesis de Diego realiza el trabajo futuro "MTAV Online" propuesto por Fierro (2024)**,
> bajo el mismo tutor. Verificar con Diego que quiere enmarcarlo así.

## Otras propuestas de Fierro = trabajo futuro de Diego (para §10)

- **Constraint Programming (MiniZinc/Gecode)** como enfoque alternativo/más flexible al MIP.
- **Nueva medida de equidad**: desviación estándar de las satisfacciones + **Frente de Pareto**
  (equidad vs. satisfacción global); posible **tercera etapa** del mecanismo.
- **Vecindad**: preferencias por ser vecino de familias específicas (pedido real de cooperativas).
- **Preferencias en bloque**: igualar prioridad de varias viviendas indiferentes.

---

## Bibliografía citable (para Apéndice H) — de Fierro 2024

- **Prino, M., Sánchez, E., Cancela, H. (2016).** *Optimal distribution of habitational units in a
  cooperative: A mathematical application to optimize satisfaction.* CLEI 2016 (XLII Latin American
  Computing Conference), pp. 1-7. doi:10.1109/CLEI.2016.7833357. → **paper fundacional del MTAV.**
- **Fagián, I., Prino, M., Sánchez, E. (2017).** *Informe módulo de taller: Módulo viviendas —
  aplicación con interfaz gráfica.* (Sup. Cancela, FING-Udelar). → versión con GUI.
- **Fierro, M. (2020).** *Informe módulo de extensión: MTAV 3.0.* (Sup. Cancela, FING-Udelar).
- **Paleo Arrarte, J. M. (2021).** *La asignación de apartamentos en cooperativas de vivienda: un
  enfoque desde el diseño de mercados.* Tesis de Maestría, FCEA-Udelar. handle:20.500.12008/31185.
- **Cancela, H., Fierro, M., Manera, A., Prino, M. (2022).** *Optimization methods for units' assignment
  in housing cooperatives.* CLAIO 2022, Buenos Aires.
- **Fierro, M. (2024).** *Asignación de viviendas en cooperativas: Programación por restricciones y
  Análisis de equidad.* Proyecto de Grado, FING-Udelar (Sup. Cancela). CC BY 4.0.
- Contexto cooperativo: FUCVAM (Historia); Housing Europe (2012); Sánchez-Laulhé et al. (2013);
  Long (2016, house allocation); Rossi/van Beek/Walsh (2006, Handbook of Constraint Programming).
