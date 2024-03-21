var rtime,
  timeout = !1;
const delta = 100,
  thumbnailGrid = _("thumbnail-grid");
function _(e) {
  return "." == e.substring(0, 1)
    ? ((e = e.replace(".", "")), document.getElementsByClassName(e))
    : document.getElementById(e);
}
function activateFiles() {
  var e = _("files").files;
  if (e.length > 0) {
    _("controls").style.opacity = 0;
    for (var t = 0; t < e.length; t++) attemptUpload(e[t], t);
  }
  regenerateUploaderForm();
}
function addThumbnail(e, t, r) {
  var a = e.name;
  e.name.length > 16 && ((a = e.name.substring(0, 12)), (a += "..."));
  var n = document.createElement("div");
  n.setAttribute("class", "drop-zone__thumb"),
    n.setAttribute("data-label", a),
    n.setAttribute("id", r);
  var o = document.createElement("div");
  o.setAttribute("class", "thumboverlay thumboverlay-black");
  var d = document.createElement("span");
  d.setAttribute("class", "percent-complete");
  var i = document.createTextNode("0%");
  d.appendChild(i), o.appendChild(d);
  var l = document.createElement("div");
  l.setAttribute("class", "loading"),
    o.appendChild(l),
    n.appendChild(o),
    thumbnailGrid.appendChild(n);
  const s = new FileReader();
  s.readAsDataURL(e),
    (s.onload = () => {
      n.style.backgroundImage = `url('${s.result}')`;
    });
}
function makeRand(e) {
  for (
    var t = "",
      r = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",
      a = r.length,
      n = 0;
    n < e;
    n++
  )
    t += r.charAt(Math.floor(Math.random() * a));
  return t;
}
function attemptUpload(e, t) {
  var r = makeRand(5);
  addThumbnail(e, t, r);
  var a = new FormData();
  a.append("file1", e);
  var n = new XMLHttpRequest();
  n.upload.addEventListener("progress", (e) => {
    progressHandler(e, t, r);
  }),
    n.upload.addEventListener("load", (e) => {
      var t = _(r).firstChild;
      for (
        t.classList.remove("thumboverlay-black"),
          t.classList.add("thumboverlay-green");
        t.firstChild;

      )
        t.removeChild(t.lastChild);
      var a = document.createElement("div");
      a.setAttribute("class", "ditch-cross");
      var n = document.createTextNode("✘");
      a.appendChild(n), t.appendChild(a), (_("controls").style.opacity = 1);
    }),
    n.open("POST", uploadUrl),
    n.setRequestHeader("trongateToken", token),
    n.send(a),
    (n.onload = function () {
      var e = _(r),
        t = e.firstChild;
      200 == n.status
        ? (t.setAttribute("id", n.responseText),
          t.lastChild.setAttribute(
            "onclick",
            "deleteImg('" + n.responseText + "')"
          ))
        : (t.classList.remove("thumboverlay-green"),
          t.classList.add("thumboverlay-red"),
          e.removeAttribute("data-label"),
          e.setAttribute("data-label", n.responseText),
          t.lastChild.setAttribute("onclick", "deleteErrorThumb('" + r + "')")),
        adjustThumbSizes();
    });
}
function progressHandler(e, t, r) {
  var a = _(r).firstChild.firstChild,
    n = (e.loaded / e.total) * 100;
  (n = Math.round(n)), a && (a.innerHTML = n + "%");
}
function deleteImg(e) {
  var t = { elId: e, target_module: targetModule, update_id: updateId };
  const r = new XMLHttpRequest();
  r.open("post", deleteUrl),
    r.setRequestHeader("Content-type", "application/json"),
    r.setRequestHeader("trongateToken", token),
    r.send(JSON.stringify(t)),
    (r.onload = function () {
      _(r.responseText).parentNode.remove();
    });
}
function initBrowse() {
  _("files").click();
}
function regenerateUploaderForm() {
  _("files").remove();
  var e = document.createElement("input");
  e.setAttribute("type", "file"),
    e.setAttribute("id", "files"),
    e.setAttribute("multiple", ""),
    e.setAttribute("onchange", "activateFiles()"),
    _("multi-form").appendChild(e);
}
function deleteErrorThumb(e) {
  _(e).remove();
}
function resizeend() {
  new Date() - rtime < delta
    ? setTimeout(resizeend, delta)
    : ((timeout = !1), adjustThumbSizes());
}
function adjustThumbSizes() {
  for (var e = _(".drop-zone__thumb"), t = 0; t < e.length; t++) {
    var r = e[t].getBoundingClientRect().width;
    e[t].style.height = r + "px";
  }
}
function watchWindowSize() {
  (rtime = new Date()),
    !1 === timeout && ((timeout = !0), setTimeout(resizeend, delta));
}
var dropZone = _("drop-zone");
dropZone.addEventListener("dragover", (e) => {
  e.preventDefault(),
    (_("drop-zone").style.border = "4px var(--primary-darker) dashed"),
    (_("drop-zone").style.backgroundColor = "#fffbdf");
}),
  dropZone.addEventListener("dragleave", (e) => {
    e.preventDefault(),
      (_("drop-zone").style.border = "4px grey dashed"),
      (_("drop-zone").style.backgroundColor = "#eee");
  }),
  dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    var t = e.dataTransfer.files;
    if (t.length > 0) {
      _("controls").style.opacity = 0;
      for (var r = 0; r < t.length; r++) attemptUpload(t[r], r);
    }
    (_("drop-zone").style.border = "4px grey dashed"),
      (_("drop-zone").style.backgroundColor = "#eee"),
      regenerateUploaderForm();
  }),
  adjustThumbSizes(),
  window.addEventListener("resize", watchWindowSize);
