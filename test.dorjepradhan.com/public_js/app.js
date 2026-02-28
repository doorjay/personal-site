(function ()
{
  let likes = 0;

  const likeBtn = document.getElementById("btnLike");
  const resetBtn = document.getElementById("btnReset");
  const errorBtn = document.getElementById("btnError");
  const likeCount = document.getElementById("likeCount");

  if (likeBtn && likeCount)
  {
    likeBtn.addEventListener("click", function ()
    {
      likes += 1;
      likeCount.textContent = String(likes);
    });
  }

  if (resetBtn && likeCount)
  {
    resetBtn.addEventListener("click", function ()
    {
      likes = 0;
      likeCount.textContent = "0";
    });
  }

  if (errorBtn)
  {
    errorBtn.addEventListener("click", function ()
    {
      // Intentionally throw an error for testing
      undefinedFunctionCall();
    });
  }
})();