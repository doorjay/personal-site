(function ()
{
  const logEl = document.getElementById("log");

  function log(message)
  {
    if (!logEl) { return; }
    const time = new Date().toLocaleTimeString();
    const line = document.createElement("div");
    line.textContent = `[${time}] ${message}`;
    logEl.prepend(line);
  }

  // Likes (Home)
  let likes = 0;
  const likeCountEl = document.getElementById("likeCount");
  const likeBtn = document.getElementById("btnLike");
  const resetBtn = document.getElementById("btnReset");
  const errorBtn = document.getElementById("btnError");

  if (likeBtn && likeCountEl)
  {
    likeBtn.addEventListener("click", function ()
    {
      likes += 1;
      likeCountEl.textContent = String(likes);
      log("Clicked Like");
    });
  }

  if (resetBtn && likeCountEl)
  {
    resetBtn.addEventListener("click", function ()
    {
      likes = 0;
      likeCountEl.textContent = "0";
      log("Reset Likes");
    });
  }

  // Trigger a real JS error (helps later with error capture)
  if (errorBtn)
  {
    errorBtn.addEventListener("click", function ()
    {
      log("Triggering JS error");
      // Intentionally undefined:
      undefinedFunctionCall();
    });
  }

  // Form (Contact): keep it simple (submit + a couple of basic input events)
  const form = document.getElementById("demoForm");
  const formStatus = document.getElementById("formStatus");

  if (form)
  {
    form.addEventListener("submit", function (e)
    {
      e.preventDefault();

      const data = new FormData(form);
      const name = String(data.get("name") || "").trim();
      const topic = String(data.get("topic") || "");
      const subscribe = data.get("subscribe") === "on";

      if (formStatus)
      {
        formStatus.textContent = `Submitted: name="${name}", topic="${topic}", subscribe=${subscribe}`;
      }

      log("Submitted form");
    });

    // Minimal “activity” signals
    form.addEventListener("input", function (e)
    {
      if (e.target && e.target.name === "name")
      {
        log("Typing in Name");
      }
    });

    form.addEventListener("change", function (e)
    {
      if (!e.target) { return; }
      if (e.target.name === "topic") { log("Changed Topic"); }
      if (e.target.name === "subscribe") { log("Toggled Subscribe"); }
    });
  }

  // Scroll (About)
  const scrollBox = document.getElementById("scrollBox");
  if (scrollBox)
  {
    scrollBox.addEventListener("scroll", function ()
    {
      log("Scrolled scroll box");
    });
  }
})();