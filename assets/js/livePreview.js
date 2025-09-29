/**
 * Converto Modelos – Live Preview de CSS e JS personalizados no editor do Elementor
 */
(function ($) {
  "use strict";

  ///////////////////////////////
  // 🔹 CSS PERSONALIZADO
  ///////////////////////////////
  function customCSSFilter(css, context) {
    try {
      if (!context || !context.model) return css;

      var model = context.model;
      var settings = model.get("settings");
      if (!settings || typeof settings.get !== "function") return css;

      var customCSS = settings.get("custom_css");
      if (!customCSS) return css;

      var selector = ".elementor-element.elementor-element-" + model.get("id");

      if (model.get("elType") === "document") {
        if (
          window.elementor &&
          elementor.config &&
          elementor.config.document &&
          elementor.config.document.settings &&
          elementor.config.document.settings.cssWrapperSelector
        ) {
          selector = elementor.config.document.settings.cssWrapperSelector;
        } else if (
          window.elementor &&
          elementor.settings &&
          elementor.settings.page &&
          elementor.settings.page.model
        ) {
          selector =
            elementor.settings.page.model.get("cssWrapperSelector") || "body";
        } else {
          selector = "body";
        }
      }

      var out =
        (css || "") +
        "\n" +
        String(customCSS).replace(/selector/g, selector);
      return out;
    } catch (e) {
      return css;
    }
  }

  ///////////////////////////////
  // 🔹 JS PERSONALIZADO
  ///////////////////////////////
  function applyElementJs($head, model) {
    if (!model || typeof model.get !== "function") return;

    var settings = model.get("settings");
    if (!settings || typeof settings.get !== "function") return;

    var customJs = settings.get("custom_js");
    if (!customJs) return;

    var id = model.get("id");
    var scriptId = "converto-custom-js-" + id;

    // remove script antigo antes de reinserir
    $head.find("#" + scriptId).remove();

    var scriptTag = document.createElement("script");
    scriptTag.id = scriptId;
    scriptTag.type = "text/javascript";
    scriptTag.text = customJs;

    $head.append(scriptTag);
  }

  function applyPageJs($head) {
    try {
      if (
        !(elementor && elementor.settings && elementor.settings.page) ||
        !elementor.settings.page.model
      ) {
        return;
      }
      var customJs = elementor.settings.page.model.get("custom_js") || "";
      var scriptId = "converto-custom-js-page";
      $head.find("#" + scriptId).remove();

      if (customJs && customJs.length > 0) {
        var scriptTag = document.createElement("script");
        scriptTag.id = scriptId;
        scriptTag.type = "text/javascript";
        scriptTag.text = customJs;
        $head.append(scriptTag);
      }
    } catch (e) {}
  }

  ///////////////////////////////
  // 🔹 INIT
  ///////////////////////////////
  function bindCssFilter() {
    if (
      !(window.elementor && elementor.hooks && typeof elementor.hooks.addFilter === "function")
    ) {
      return false;
    }
    if (!bindCssFilter._bound) {
      elementor.hooks.addFilter("editor/style/styleText", customCSSFilter);
      bindCssFilter._bound = true;
    }
    return true;
  }

  function initJsPreview() {
    if (!(window.elementor && elementor.$previewContents)) return;

    var $head = elementor.$previewContents.find("head");

    // Aplica JS já existente em todos os elementos
    try {
      if (elementor.elements && typeof elementor.elements.each === "function") {
        elementor.elements.each(function (model) {
          applyElementJs($head, model);
        });
      }
    } catch (e) {}

    // Aplica JS da página
    applyPageJs($head);

    // Atualização em tempo real: widgets/seções
    try {
      if (
        elementor.hooks &&
        typeof elementor.hooks.addAction === "function"
      ) {
        elementor.hooks.addAction("panel/open_editor", function (panel, model) {
          applyElementJs($head, model);
          if (model && typeof model.on === "function") {
            model.on("change:settings", function () {
              if (model.changed && typeof model.changed.custom_js !== "undefined") {
                applyElementJs($head, model);
              }
            });
          }
        });
      }
    } catch (e) {}

    // Atualização em tempo real: JS da página
    try {
      if (
        elementor.channels &&
        elementor.channels.editor &&
        typeof elementor.channels.editor.on === "function"
      ) {
        elementor.channels.editor.on("change:document:settings", function (m) {
          if (m && m.changed && typeof m.changed.custom_js !== "undefined") {
            applyPageJs($head);
          }
        });
      }
    } catch (e) {}
  }

  ///////////////////////////////
  // 🔹 BOOTSTRAP
  ///////////////////////////////
  function boot() {
    if (bindCssFilter()) {
      // quando preview carregar, aplica JS também
      elementor.on("preview:loaded", initJsPreview);
      return;
    }
    var tries = 0;
    var t = setInterval(function () {
      tries++;
      if (bindCssFilter()) {
        elementor.on("preview:loaded", initJsPreview);
        clearInterval(t);
      }
      if (tries > 200) clearInterval(t);
    }, 50);
  }

  $(boot);
})(jQuery);