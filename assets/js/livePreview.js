/**
 * Converto Modelos – Live Preview de CSS personalizado no editor do Elementor
 * Estratégia: mesmo padrão do tema Converto (hook editor/style/styleText)
 */
(function ($) {
  "use strict";

  function customCSSFilter(css, context) {
    try {
      if (!context || !context.model) return css;

      var model = context.model;
      var settings = model.get('settings');
      if (!settings || typeof settings.get !== 'function') return css;

      var customCSS = settings.get('custom_css');
      if (!customCSS) return css;

      // Define o seletor correto: widget/section/column vs document (página)
      var selector = '.elementor-element.elementor-element-' + model.get('id');

      if (model.get('elType') === 'document') {
        // Tenta pegar o wrapper configurado pelo Elementor
        if (window.elementor &&
            elementor.config &&
            elementor.config.document &&
            elementor.config.document.settings &&
            elementor.config.document.settings.cssWrapperSelector) {

          selector = elementor.config.document.settings.cssWrapperSelector;

        } else if (window.elementor &&
                   elementor.settings &&
                   elementor.settings.page &&
                   elementor.settings.page.model) {

          selector = elementor.settings.page.model.get('cssWrapperSelector') || 'body';
        } else {
          selector = 'body';
        }
      }

      // Concatena o CSS existente + o custom, substituindo "selector"
      var out = (css || '') + "\n" + String(customCSS).replace(/selector/g, selector);
      return out;
    } catch (e) {
      // Em caso de erro, não quebra o editor
      return css;
    }
  }

  function bindOnce() {
    if (!(window.elementor && elementor.hooks && typeof elementor.hooks.addFilter === 'function')) {
      return false;
    }
    if (!bindOnce._bound) {
      elementor.hooks.addFilter('editor/style/styleText', customCSSFilter);
      bindOnce._bound = true;
    }
    return true;
  }

  // Garante o bind mesmo se o Elementor carregar depois
  function boot() {
    if (bindOnce()) return;
    var tries = 0;
    var t = setInterval(function () {
      tries++;
      if (bindOnce() || tries > 200) {
        clearInterval(t);
      }
    }, 50);
  }

  // Inicia quando o editor carregar
  $(boot);

})(jQuery);