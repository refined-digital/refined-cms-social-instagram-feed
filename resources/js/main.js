window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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

  return window.axios
    .post('/instagram-feed', data)
    .then(results => {
      let html;
      if (results.data.success) {
        if (results.data.data) {
          results.data.data.forEach(item => {
            html = options.template;
            html = html.replace('[link]', item.permalink);
            html = html.replace('[image]', item.media_url);
            html = html.replace('[caption]', item.caption || '');
          });
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
    })
}
