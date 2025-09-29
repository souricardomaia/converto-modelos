/**
 * Converto Modelos – Live Preview de CSS e JS personalizados no editor do Elementor
 */
(function ($) {
  "use strict";

  ///////////////////////////////
  // 🔹 CSS PERSONALIZADO
  ///////////////////////////////
  function customCSSFilter(css, context) {
    if (!context || !context.model) return css;

    var model = context.model;
    var settings = model.get("settings");
    if (!settings || typeof settings.get !== "function") return css;

    var customCSS = settings.get("custom_css");
    if (!customCSS) return css;

    var selector = ".elementor-element.elementor-element-" + model.get("id");

    if (model.get("elType") === "document") {
      selector =
        (elementor.config &&
          elementor.config.document &&
          elementor.config.document.settings &&
          elementor.config.document.settings.cssWrapperSelector) ||
        (elementor.settings?.page?.model?.get("cssWrapperSelector")) ||
        "body";
    }

    return (css || "") + "\n" + String(customCSS).replace(/selector/g, selector);
  }

  ///////////////////////////////
  // 🔹 JS PERSONALIZADO
  ///////////////////////////////
  function runCustomJs(model, view) {
    if (!model || !view) return;

    const settings = model.get("settings");
    const code = (settings.get("custom_js") || "").trim();
    const $selector = view.$el;

    if (!code) return;

    try {
      (function (selector, $) {
        eval(code);
      })($selector, jQuery);
      $selector.data("js-ran", true);
    } catch (e) {
      console.error("Erro no Custom JS:", e);
    }
  }

  function refreshCustomJs(panel, model, view) {
    if (!model || !view) return;
    let timeout;
    const run = () => runCustomJs(model, view);

    // roda imediatamente ao abrir o painel
    run();

    // reexecuta sempre que o campo mudar (com debounce)
    model.get("settings").on("change:custom_js", () => {
      clearTimeout(timeout);
      timeout = setTimeout(run, 400);
    });
  }

  ///////////////////////////////
  // 🔹 INIT
  ///////////////////////////////
  function bindFilters() {
    if (!(elementor && elementor.hooks && typeof elementor.hooks.addFilter === "function")) {
      return false;
    }
    if (!bindFilters._bound) {
      // CSS
      elementor.hooks.addFilter("editor/style/styleText", customCSSFilter);

      // JS (vários tipos de elementos)
      ["widget", "section", "column", "container", "document"].forEach((type) => {
        elementor.hooks.addAction("panel/open_editor/" + type, refreshCustomJs);
      });

      bindFilters._bound = true;
    }
    return true;
  }

  function onPreviewLoaded() {
    // executa em todos os elementos que já têm custom_js
    elementor.elements?.models?.forEach((model) => {
      const code = model.get("settings")?.get("custom_js");
      if (!code || !code.trim()) return;

      const view = elementor.getPanelView()?.children?.find(
        (v) => v.model && v.model.id === model.id
      );
      if (!view) return;

      runCustomJs(model, view);
    });
  }

  function boot() {
    if (bindFilters()) {
      elementor.on("preview:loaded", onPreviewLoaded);
      return;
    }
    var tries = 0;
    var t = setInterval(function () {
      tries++;
      if (bindFilters()) {
        elementor.on("preview:loaded", onPreviewLoaded);
        clearInterval(t);
      }
      if (tries > 200) clearInterval(t);
    }, 50);
  }

  $(boot);
})(jQuery);