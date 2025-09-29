/**
 * Converto Modelos – Live Preview de CSS e JS personalizados (Editor Elementor)
 */
(function ($) {
  "use strict";

  // ========= CSS =========
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

  // ========= JS =========
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

  function runCustomJsForModel(model) {
    if (!model) return;

    var settings = model.get("settings");
    var code = (settings.get("custom_js") || "").trim();
    var view = elementor.getView ? elementor.getView(model.id) : null;
    if (!view || !view.$el) return;

    var $el = view.$el;

    // salva baseline e reseta antes de aplicar
    if ($el.data("cm-js-baseline-style") === undefined) {
      $el.data("cm-js-baseline-style", $el.attr("style") || "");
    }
    if ($el.data("cm-js-baseline-class") === undefined) {
      $el.data("cm-js-baseline-class", $el.attr("class") || "");
    }
    resetBaseline($el);

    if (!code) return;

    try {
      (function (selector, $) {
        eval(code);
      })($el, jQuery);
      $el.data("js-ran", true);
    } catch (e) {
      console.error("Erro no Custom JS:", e);
    }
  }

  function refreshOnPanelOpen(panel, model) {
    if (!model) return;
    var timeout;
    var run = function () { runCustomJsForModel(model); };

    // roda imediatamente
    run();

    // reexecuta (debounce) sempre que o campo mudar
    model.get("settings")
      .off("change:custom_js.cm")
      .on("change:custom_js.cm", function () {
        clearTimeout(timeout);
        timeout = setTimeout(run, 400);
      });
  }

  // ========= INIT =========
  function bind() {
    if (!(elementor && elementor.hooks && typeof elementor.hooks.addFilter === "function")) return false;
    if (bind._bound) return true;

    elementor.hooks.addFilter("editor/style/styleText", customCSSFilter);

    ["widget", "section", "column", "container", "document"].forEach(function (type) {
      elementor.hooks.addAction("panel/open_editor/" + type, refreshOnPanelOpen);
    });

    bind._bound = true;
    return true;
  }

  function onPreviewLoaded() {
    var $doc = elementor.$previewContents || $(document);

    // 1) roda via atributo data-custom-js (se já existir no preview)
    $doc.find("[data-custom-js]").each(function () {
      var $el = $(this);
      if ($el.data("js-ran")) return;
      var encoded = $el.attr("data-custom-js");
      if (!encoded) return;

      // salva baseline uma vez
      if ($el.data("cm-js-baseline-style") === undefined) {
        $el.data("cm-js-baseline-style", $el.attr("style") || "");
      }
      if ($el.data("cm-js-baseline-class") === undefined) {
        $el.data("cm-js-baseline-class", $el.attr("class") || "");
      }
      resetBaseline($el);

      try {
        var code = atob(encoded);
        (function (selector, $) { eval(code); })($el, jQuery);
        $el.data("js-ran", true);
      } catch (e) {
        console.error("Erro no Custom JS (preview via data-custom-js):", e);
      }
    });

    // 2) roda para todos os modelos com custom_js
    (elementor.elements?.models || []).forEach(function (model) {
      var code = model.get("settings")?.get("custom_js");
      if (code && code.trim()) runCustomJsForModel(model);
      if (code === "") runCustomJsForModel(model); // garante reset quando o campo fica vazio
    });
  }

  function boot() {
    if (bind()) {
      elementor.on("preview:loaded", onPreviewLoaded);
      elementor.on("document:loaded", bind);
    } else {
      var tries = 0;
      var t = setInterval(function () {
        tries++;
        if (bind()) {
          elementor.on("preview:loaded", onPreviewLoaded);
          elementor.on("document:loaded", bind);
          clearInterval(t);
        }
        if (tries > 200) clearInterval(t);
      }, 50);
    }
  }

  $(function () { if (window.elementor) boot(); });
})(jQuery);