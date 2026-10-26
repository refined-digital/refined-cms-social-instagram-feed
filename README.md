# RefinedCMS Instagram Feed

Shows the latest posts from an Instagram business account on a RefinedCMS site. Adds an **Instagram** item to the
admin menu (`/refined/instagram`) for the app credentials, and a public `POST /instagram-feed` endpoint that returns
`{success, data[, message]}`.

Requires PHP 8.2+ and `refineddigital/cms` 1.28.5+ (tested on 1.38 / Laravel 13 / PHP 8.4).

## Install

```bash
composer require refineddigital/cms-social-instagram-feed
php artisan refinedCMS:install-social-instagram
```

The install command links `public/vendor/refined/instagram-feed` to the prebuilt script. A Vite site doesn't need that
link, but the command is safe to run either way.

Then in the admin, go to **Instagram**, enter the Client ID, Client Secret and Redirect Url
(`https://your-site/refined/instagram`) of your Instagram app, save, and click **Connect**. The long lived token is
stored in `storage/app/private/instagram-token.json` (on the `local` disk) and refreshed automatically once it is
within 30 days of expiring.

## Using it on a Vite site

The script has no dependencies. Import it straight from the vendor folder in your site's JS (e.g. `resources/js/main.js`):

```js
import { instagramFeed } from '../../vendor/refineddigital/cms-social-instagram-feed/resources/js/instagram-feed.js'

instagramFeed({
  el: '.instagram-feed',
  limit: 8,
  template: `<a href="[link]" target="_blank"><img src="[image]" alt="[caption]"/><time>[date|D MMM YYYY]</time></a>`,
})
```

Templates that call `window.instagramFeed(...)` inline can keep doing so. Import the global entry instead, which
sets `window.instagramFeed`:

```js
import '../../vendor/refineddigital/cms-social-instagram-feed/resources/js/main.js'
```

The page needs the usual `<meta name="csrf-token" content="{{ csrf_token() }}">` in its head. The request is a
same-origin `fetch`, so Laravel also accepts it on its origin check.

### Options

| Option     | Default            | |
|------------|--------------------|---|
| `el`       | `'.instagram-feed'` | selector or element the items are appended to |
| `limit`    | `12`               | number of posts (1-100) |
| `template` | a linked `<img>`   | html for each post |
| `url`      | `'/instagram-feed'` | the feed endpoint |

Placeholders: `[link]`, `[image]` (the video thumbnail for videos), `[caption]` and `[date]` / `[date|FORMAT]`.
Values are html escaped. `FORMAT` takes dayjs-style tokens and is shown in UTC, as before: `YYYY YY M MM MMM MMMM D DD Do d dd ddd dddd H HH h hh k kk m mm s ss SSS A a Q X x Z ZZ`.
The default is `DD/MM/YYYY HH:mm:ss`.

`instagramFeed()` returns a promise of the posts it rendered. When the feed can't load (not connected, Instagram
error, network) it renders nothing, logs a `console.warn` and resolves with `[]`. It only rejects when `el` is not found.

The module also exports `fetchFeed`, `renderItem` and `formatDate` for custom rendering.

## Sites without Vite

Load the prebuilt script (from `npm run build`, at `assets/js/main.js`) through the install command's link:

```html
<script src="{{ asset('vendor/refined/instagram-feed/js/main.js') }}"></script>
<script>window.instagramFeed({ limit: 8 })</script>
```
