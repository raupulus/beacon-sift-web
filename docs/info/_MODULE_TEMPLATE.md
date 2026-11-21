# Plantilla de documentación de módulo (`_MODULE_TEMPLATE.md`)

Usa esta plantilla al crear un nuevo `.md` en `docs/info/` (eliminando este bloque introductorio). No inventes apartados si no aplican: indícalo brevemente o márcalo como `⚠️ sin verificar` hasta contrastarlo con el código.

---

# `<nombre-del-modulo>`

> Ficheros cubiertos: `app/...`, `routes/...`, `resources/...`

## 1. Qué hace y qué NO hace

- **Hace:** ...
- **NO hace:** ...

## 2. Modelo de datos

Tablas PostgreSQL, relaciones, índices específicos, DTOs o enums implicados.

## 3. Flujos principales

Secuencia paso a paso de las operaciones clave.

## 4. Puntos de entrada (con auth, permisos y límites)

Rutas web/API, comandos Artisan o eventos expuestos, incluyendo *rate limits* o guardias.

## 5. Dependencias en ambos sentidos

- **Depende de:** servicios internos, librerías o APIs externas que consume.
- **Consumido por:** controladores, vistas, bots externos o comandos que dependen de este módulo.

## 6. Configuración (variables, valor por defecto y efecto)

| Variable (`.env`) | Clave `config()` | Valor por defecto | Efecto |
|---|---|---|---|
| ... | ... | ... | ... |

## 7. Trampas conocidas

Casos límite, diferencias PostgreSQL vs SQLite en tests, contratos públicos consumidos por bots o detalles que pueden causar regresiones.

## 8. Tests que lo cubren

- `tests/...` — qué escenarios verifica realmente.

## 9. Pendiente real

Trabajo o mejoras verificadas como ausentes en el código actual (no deseos genéricos).

---

> Creado: 2026-11-20 · Última revisión: 2026-11-21
