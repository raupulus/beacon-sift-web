# Módulo: Enums del Sistema

*Última actualización: 2026-11-20*

> Backed enums en PHP 8.5 ubicados en `app/Enums/`.

---

## 1. `App\Enums\Priority`
- Casos: `Low` (`low`, peso `1`), `Medium` (`medium`, peso `2`), `High` (`high`, peso `3`), `Critical` (`critical`, peso `4`).
- Métodos: `weight()`, `label()`, `badgeClasses()`, `tryFromNormalized(?string)`, `catalog()`.

## 2. `App\Enums\SourceFormat`
- Casos: `Rss` (`rss`), `Atom` (`atom`), `Json` (`json`), `CveNvd` (`cve_nvd`).
- Métodos: `label()`.

## 3. `App\Enums\FetchStatus`
- Casos: `Success` (`success`), `NotModified` (`not_modified`), `Error` (`error`).
