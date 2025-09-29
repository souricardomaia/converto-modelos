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
  // 🔹 HELPERS (preview)
  ///////////////////////////////
  function saveBaseline($el) {
    if ($el.data("cm-js-baseline-style") === undefined) {
      $el.data("cm-js-baseline-style", $el.attr("style") || "");
    }
    if ($el.data("cm-js-baseline-class") === undefined) {
      $el.data("cm-js-baseline-class", $el.attr("class") || "");
    }
  }

  function resetBaseline($el) {
    if (!$el || !$el.length) return;
    var baseStyle = $el.data("cm-js-baseline-style");
    var baseClass = $el.data("cm-js-baseline-class");

    if (baseStyle !== undefined) {
      if (baseStyle) $el.attr("style", baseStyle);
      else $el.removeAttr("style");
    }
    if (baseClass !== undefined) {
      $el.attr("class", baseClass);
    }
    $el.removeData("js-ran");
  }

  function getView(model, viewFromHook) {
    if (viewFromHook && viewFromHook.$el) return viewFromHook;
    if (elementor.getView) {
      var v = elementor.getView(model.id);
      if (v && v.$el) return v;
    }
    // Fallback: tenta localizar via filhos do painel
    try {
      var v2 = elementor.getPanelView()?.children?.find(function(v){
        return v.model && v.model.id === model.id;
      });
      if (v2 && v2.$el) return v2;
    } catch(_) {}
    return null;
  }

  ///////////////////////////////
  // 🔹 EXECUÇÃO DO JS
  ///////////////////////////////
  function runCustomJsFromAttr($root) {
    $root.find("[data-custom-js]").each(function () {
      var $el = $(this);
      if ($el.data("js-ran")) return;
      var encoded = $el.attr("data-custom-js");
      if (!encoded) return;

      saveBaseline($el);
      resetBaseline($el);

      try {
        var code = atob(encoded);
        (function (selector, $) { eval(code); })($el, jQuery);
        $el.data("js-ran", true);
      } catch (e) {
        console.error("Erro no Custom JS (preview via data-custom-js):", e);
      }
    });
  }

  function runCustomJsFromSettings(model, viewFromHook) {
    if (!model) return;

    var settings = model.get("settings");
    var code = (settings.get("custom_js") || "").trim();
    var view = getView(model, viewFromHook);
    if (!view || !view.$el) return;

    var $el = view.$el;

    saveBaseline($el);
    resetBaseline($el);

    if (!code) return;

    try {
      (function (selector, $) {
        eval(code);
      })($el, jQuery);
      $el.data("js-ran", true);
    } catch (e) {
      console.error("Erro no Custom JS (preview via settings):", e);
    }
  }

  function refreshCustomJs(panel, model, view) {
    if (!model) return;
    var timeout;
    var run = function(){ runCustomJsFromSettings(model, view); };

    // roda imediatamente ao abrir o painel
    run();

    // reexecuta sempre que o campo mudar (com debounce)
    model.get("settings")
      .off("change:custom_js.cm")
      .on("change:custom_js.cm", function(){
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
      ["widget", "section", "column", "container", "document"].forEach(function(type){
        elementor.hooks.addAction("panel/open_editor/" + type, refreshCustomJs);
      });

      bindFilters._bound = true;
    }
    return true;
  }

  function onPreviewLoaded() {
    var $doc = elementor.$previewContents || $(document);

    // 1) executa qualquer data-custom-js já renderizado
    runCustomJsFromAttr($doc);

    // 2) executa para modelos que já têm código (e reseta quando vazio)
    (elementor.elements?.models || []).forEach(function(model){
      var code = model.get("settings")?.get("custom_js");
      if (code !== undefined) {
        runCustomJsFromSettings(model, null);
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