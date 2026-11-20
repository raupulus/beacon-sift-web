# Módulo: Servicio SEO (`SeoService`)

*Última actualización: 2026-11-20*

> Servicio ligero scoped para inyectar título, descripción, URL canónica e imagen Open Graph / Twitter Card en `resources/views/layouts/app.blade.php`.

---

## Archivo principal

- `app/Services/SeoService.php` (registrado como `scoped` en `App\Providers\AppServiceProvider`).

## Métodos públicos

- `setTitle(string $title): static` / `getTitle(): string`
- `setDescription(string $description): static` / `getDescription(): string`
- `setUrl(string $url): static` / `getUrl(): string`
- `setImage(string $image): static` / `getImage(): string`

Si una vista no especifica imagen, `layouts/app.blade.php` utiliza por defecto `/images/redes-sociales/og-image-1200x630.png`.
