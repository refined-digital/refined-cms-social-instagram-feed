/**
 * Instagram feed for RefinedCMS, no dependencies.
 *
 * import { instagramFeed } from '.../cms-social-instagram-feed/resources/js/instagram-feed.js'
 * instagramFeed({ el: '.instagram-feed', limit: 8, template: '<a href="[link]"><img src="[image]" alt="[caption]"></a>' })
 *
 * Template placeholders: [link], [image], [caption], [date] and [date|FORMAT] (dayjs style tokens, in UTC).
 */

export const defaultOptions = {
  el: '.instagram-feed',
  limit: 12,
  url: '/instagram-feed',
  template: `<a href="[link]" target="_blank">
        <img src="[image]" alt="[caption]"/>
      </a>
    `,
}

export const defaultDateFormat = 'DD/MM/YYYY HH:mm:ss'

const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

const pad = (value, length = 2) => String(value).padStart(length, '0')

const ordinal = (n) => {
  const s = ['th', 'st', 'nd', 'rd']
  const v = n % 100
  return n + (s[(v - 20) % 10] || s[v] || s[0])
}

/**
 * Parses an instagram timestamp ("2024-05-01T10:00:00+0000"), which Safari can't read without the offset colon.
 */
export const parseTimestamp = (timestamp) => {
  if (timestamp instanceof Date) {
    return timestamp
  }
  return new Date(String(timestamp).replace(/([+-]\d{2})(\d{2})$/, '$1:$2'))
}

/**
 * Formats a date in UTC with the dayjs tokens (plus advancedFormat's Q, Do, k, kk, X, x) the old bundle supported.
 */
export const formatDate = (timestamp, format = defaultDateFormat) => {
  const date = parseTimestamp(timestamp)
  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const Y = date.getUTCFullYear()
  const M = date.getUTCMonth()
  const D = date.getUTCDate()
  const d = date.getUTCDay()
  const H = date.getUTCHours()
  const m = date.getUTCMinutes()
  const s = date.getUTCSeconds()
  const h = H % 12 || 12

  const tokens = {
    YYYY: () => pad(Y, 4),
    YY: () => pad(Y % 100),
    M: () => M + 1,
    MM: () => pad(M + 1),
    MMM: () => months[M].slice(0, 3),
    MMMM: () => months[M],
    D: () => D,
    DD: () => pad(D),
    Do: () => ordinal(D),
    d: () => d,
    dd: () => days[d].slice(0, 2),
    ddd: () => days[d].slice(0, 3),
    dddd: () => days[d],
    H: () => H,
    HH: () => pad(H),
    h: () => h,
    hh: () => pad(h),
    k: () => H || 24,
    kk: () => pad(H || 24),
    m: () => m,
    mm: () => pad(m),
    s: () => s,
    ss: () => pad(s),
    SSS: () => pad(date.getUTCMilliseconds(), 3),
    A: () => (H < 12 ? 'AM' : 'PM'),
    a: () => (H < 12 ? 'am' : 'pm'),
    Q: () => Math.floor(M / 3) + 1,
    Z: () => '+00:00',
    ZZ: () => '+0000',
    X: () => Math.floor(date.getTime() / 1000),
    x: () => date.getTime(),
  }

  return format.replace(
    /\[([^\]]+)]|Do|YYYY|YY|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|k{1,2}|m{1,2}|s{1,2}|SSS|Z{1,2}|Q|X|x|A|a/g,
    (match, escaped) => escaped ?? String(tokens[match]()),
  )
}

const escapeHtml = (value) =>
  String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;')

/**
 * Fills the template for one media item.
 */
export const renderItem = (template, item) => {
  let html = template

  if (html.includes('[date')) {
    html = html.replace(/\[(date.*?)\]/g, (match, inner) => {
      const bits = inner.split('|')
      return escapeHtml(formatDate(item.timestamp, bits.length > 1 ? bits[1] : defaultDateFormat))
    })
  }

  return html
    .replaceAll('[link]', escapeHtml(item.permalink))
    .replaceAll('[image]', escapeHtml(item.thumbnail_url || item.media_url))
    .replaceAll('[caption]', escapeHtml(item.caption))
}

/**
 * Loads the feed from the package's public endpoint.
 *
 * @returns {Promise<{success: boolean, data: Array, message?: string}>}
 */
export const fetchFeed = async ({ url = defaultOptions.url, limit = defaultOptions.limit } = {}) => {
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }

  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    headers['X-CSRF-TOKEN'] = token.content
  }

  const response = await fetch(url, {
    method: 'POST',
    headers,
    credentials: 'same-origin',
    body: JSON.stringify({ limit }),
  })

  if (!response.ok) {
    return { success: false, data: [], message: `Instagram feed request failed (${response.status})` }
  }

  return response.json()
}

/**
 * Renders the feed into options.el, resolves with the media items that were rendered.
 */
export const instagramFeed = async (options = {}) => {
  options = Object.assign({}, defaultOptions, options)

  const element = typeof options.el === 'string' ? document.querySelector(options.el) : options.el
  if (!element) {
    throw new Error(`Can not find element ${options.el}`)
  }

  let results
  try {
    results = await fetchFeed(options)
  } catch (error) {
    results = { success: false, data: [], message: error.message }
  }

  if (!results.success) {
    console.warn('[instagram-feed]', results.message || 'Unable to load the Instagram feed')
    return []
  }

  const items = Array.isArray(results.data) ? results.data : []
  if (items.length) {
    element.insertAdjacentHTML('beforeend', items.map((item) => renderItem(options.template, item)).join(''))
  }

  return items
}

export default instagramFeed
