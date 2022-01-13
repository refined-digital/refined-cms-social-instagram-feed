window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const dayjs = require('dayjs');
const utc = require('dayjs/plugin/utc');
const advancedFormat = require('dayjs/plugin/advancedFormat');
dayjs.extend(utc);
dayjs.extend(advancedFormat);

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

window.instagramFeed = async (options) => {
  const defaultOptions = {
    el: '.instagram-feed',
    limit: 12,
    template: `<a href="[link]" target="_blank">
        <img src="[image]" alt="[caption]"/>
      </a>
    `
  }

  const defaultDateFormat = 'DD/MM/YYYY HH:mm:ss';

  options = Object.assign(defaultOptions, options);

  const element = document.querySelector(options.el);
  const data = {
    limit: options.limit
  }
  const urlSearchParams = new URLSearchParams(window.location.search);
  const params = Object.fromEntries(urlSearchParams.entries());
  if (params.code) {
    data.code = params.code;
  }

  const configureDate = function(html, timestamp) {
    const date = dayjs.utc(timestamp);

    const dateMatches = html.matchAll(/\[(date.*?)\]/g);

    const search = [];
    const replace = [];

    Array.from(dateMatches).forEach(match => {
      const dateBits = match[1].split('|');
      const localDate = (dateBits.length > 1)
        ? date.format(dateBits[1])
        : date.format(defaultDateFormat);

      search.push(match[0]);
      replace.push(localDate);
    })

    if (search.length) {
      search.forEach((item, index) => {
        html = html.replaceAll(item, replace[index]);
      })
    }

    return html;
  }

  return new Promise(function (resolve) {

  window.axios
    .post('/instagram-feed', data)
    .then(results => {
      let html;
      if (results.data.success) {
        if (results.data.data) {
          const htmlItems = [];
          results.data.data.forEach(item => {
            let htmlItem = options.template;
            if (htmlItem.includes('[date')) {
              htmlItem = configureDate(htmlItem, item.timestamp);
            }
            htmlItem = htmlItem.replaceAll('[link]', item.permalink);
            htmlItem = htmlItem.replaceAll('[image]', item.media_url);
            htmlItem = htmlItem.replaceAll('[caption]', item.caption || '');
            htmlItems.push(htmlItem);
          });
          html = htmlItems.join('');
        } else if (results.data.message) {
          html = results.data.message;
        }
      } else {
        if (results.data.link) {
          html = results.data.link;
        } else {
          html = results.data.message;
        }
      }

      element.insertAdjacentHTML('beforeend', html);

      resolve()
    })
  })

}
