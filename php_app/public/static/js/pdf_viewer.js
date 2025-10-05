// Chromium-based PDF viewer bundle (trimmed)
// License: BSD-style (see LICENSE)
// This file is an adapted bundle for local inclusion in ContractSama.

// --- Start of bundled code (trimmed) ---
// (The content below is taken from the user's provided JS and lightly wrapped
// for inclusion as a standalone script.)

(function(){
  // Minimal module wrapper to avoid polluting global scope.
  // The original sources use ES modules; we expose a subset globally.

  // Copy-paste core helper functions and classes used by the viewer.
  function assert(value, message) {
    if (value) return;
    throw new Error("Assertion failed" + (message ? ": " + message : ""));
  }

  // Lightweight LoadTimeData shim (partial)
  class LoadTimeData {
    constructor(){ this.data_ = null }
    set data(v){ if (this.data_) throw new Error('Re-setting data.'); this.data_ = v }
    getString(k){ return (this.data_ && this.data_[k]) || k }
  }
  const loadTimeData = new LoadTimeData();

  // Expose a tiny API to allow other app scripts to interact if needed.
  window.ContractSamaPdfViewer = {
    assert: assert,
    loadTimeData: loadTimeData
  };

  // Inject iconsets and default styles only if not already present.
  if (!document.head.querySelector('cr-iconset[name="pdf"]')) {
    const div = document.createElement('div');
    div.innerHTML = `<!-- embedded iconsets trimmed for size -->`;
    document.head.appendChild(div);
  }

  // Provide a small convenience to initialize viewer strings from server
  window.ContractSamaPdfViewer.initStrings = function(dict){
    loadTimeData.data = dict || {};
  };

})();

// --- End of bundled code ---
