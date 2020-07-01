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

  return window.axios
    .post('/instagram-feed', {
      limit: options.limit
    })
    .then(results => {
      results.data.forEach(item => {
        let html = options.template;
        html = html.replace('[link]', item.permalink);
        html = html.replace('[image]', item.media_url);
        html = html.replace('[caption]', item.caption || '');

        element.insertAdjacentHTML('beforeend', html);
      });
    })
}
