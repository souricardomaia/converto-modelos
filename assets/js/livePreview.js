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
        (elementor.config?.document?.settings?.cssWrapperSelector) ||
        (elementor.settings?.page?.model?.get("cssWrapperSelector")) ||
        "body";
    }

    return (css || "") + "\n" + String(customCSS).replace(/selector/g, selector);
  }

  ///////////////////////////////
  // 🔹 JS PERSONALIZADO
  ///////////////////////////////
  function runCustomJs(model) {
    if (!model) return;

    const settings = model.get("settings");
    const code = (settings.get("custom_js") || "").trim();
    if (!code) return;

    // tenta pegar a view renderizada
    const view = elementor.getView(model.id);
    if (!view || !view.$el) return;

    const $selector = view.$el;

    try {
      (function (selector, $) {
        eval(code);
      })($selector, jQuery);
      $selector.data("js-ran", true);
    } catch (e) {
      console.error("Erro no Custom JS:", e, code);
    }
  }

  function refreshCustomJs(panel, model) {
    if (!model) return;
    let timeout;

    const run = () => runCustomJs(model);

    // roda imediatamente ao abrir
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
    if (!(elementor?.hooks?.addFilter)) return false;
    if (!bindFilters._bound) {
      // CSS
      elementor.hooks.addFilter("editor/style/styleText", customCSSFilter);

      // JS: quando abre o painel de edição
      ["widget", "section", "column", "container", "document"].forEach((type) => {
        elementor.hooks.addAction("panel/open_editor/" + type, refreshCustomJs);
      });

      bindFilters._bound = true;
    }
    return true;
  }

  function onPreviewLoaded() {
    // roda JS de todos os elementos que já têm código
    elementor.elements?.models?.forEach((model) => {
      const code = model.get("settings")?.get("custom_js");
      if (code && code.trim()) {
        runCustomJs(model);
      }
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