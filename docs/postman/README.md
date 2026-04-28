# CineNow – Postman Collection

This folder contains a Postman collection and environment for the CineNow Laravel OTT API.

## Files

- `CineNow.postman_collection.json` – all API endpoints, grouped by module.
- `CineNow.postman_environment.json` – variables (`base_url`, `token`, common IDs).

## Import

1. Open Postman → **Import**.
2. Drop both JSON files in. Select the **CineNow Local** environment from the top‑right dropdown.
3. Edit `base_url` to match your setup, e.g.:
   - XAMPP: `http://localhost/cinenow/public`
   - Laravel serve: `http://127.0.0.1:8000`
   - Production: `https://your-domain.com`

## Authentication

The API uses **Laravel Sanctum** bearer tokens.

1. Run **Auth → Login** with valid `email` / `password`.
2. The included test script automatically stores the returned token into the collection variable `{{token}}`.
3. All other requests inherit `Authorization: Bearer {{token}}` from the collection's auth settings.

To log out: run **Auth → Logout** (clears the token server‑side). You can also clear `{{token}}` manually in the environment.

## Sections

| Folder | Purpose |
| --- | --- |
| Auth | Register / Login / Social login / Password / OTP / PIN / Parental lock |
| TV Device Pairing | TV app pairing flow (`/api/tv/*`) |
| Dashboard / Dashboard V2 | Home screen data, trending, banners, global search |
| Movies | Movie listings, details, top 10, popular, free, most‑liked, etc. |
| TV Shows | TV show listings, details, seasons, episodes |
| Videos | Short / standalone video content |
| Live TV | Live channels, categories, EPG |
| Genres & Languages | Genre / language masters and user favourites |
| Cast & Crew | Personalities listing & user favourites |
| User Profile | Profile, account settings, device/session logout, watch history |
| Multi Profile | Netflix‑style multiple profiles per account + kids PIN |
| Search History | Recent searches per user |
| Watchlist & Continue Watch | Add / remove / list watchlist and continue‑watching |
| Ratings, Likes & Reminders | Reviews, likes, downloads, reminders, view counters |
| Pay‑Per‑View & Payments | PPV catalogue, purchase, access checks, HyperPay |
| Subscriptions | Plans, plan limitations, purchase, history, cancel |
| Coupons | Coupon CRUD (admin) |
| Pages & FAQ | CMS pages and FAQ |
| Notifications | In‑app notifications CRUD |
| Ads | VAST / custom ads feeds used by the video player |
| Setting | Generic key‑value settings resource |

## Notes / Conventions

- Every endpoint lives under the `/api` prefix (Laravel's default API routing).
- List endpoints generally accept some of: `page`, `limit`, `lang`, `type`, `genre_id`, `language_id`, `country_id`, `search`, `sort`. Only the most common ones are pre‑filled; add more as query params when needed.
- Many write endpoints expect `entertainment_id` + `type` (`movie` / `tvshow` / `episode` / `video`).
- Requests use `application/x-www-form-urlencoded` because the Laravel controllers read from `$request->input()`. Switch to raw JSON if your client sends `Content-Type: application/json`.
- Some V1 endpoints are duplicated as V2 variants (`/api/v2/...`) returning newer transformer shapes; prefer V2 for new integrations.
