function tgpInsertHeadline() {
  tgpReset(["selectedRange", "codeviews", "customModals", "toolbars"]);

  let newHeadline = document.createElement("h1");
  let starterHeadlines = [
    "First",
    "Second",
    "Third",
    "Fourth",
    "Fifth",
    "Sixth",
    "Seventh",
    "Another",
    "Yet Another",
  ];
  let numStarterHeadlines = starterHeadlines.length;

  //how many headlines do we have
  let allHeadlines =
    trongatePagesObj.defaultActiveElParent.querySelectorAll("h1");
  let numHeadlinesSoFar = allHeadlines.length;

  if (starterHeadlines[numHeadlinesSoFar]) {
    var starterHeadlineText = starterHeadlines[numHeadlinesSoFar] + " Headline";
  } else {
    var starterHeadlineText = "You Must Really Like Headlines!";
  }

  let newHeadlineText = document.createTextNode(starterHeadlineText);
  newHeadline.appendChild(newHeadlineText);
  trongatePagesObj.targetNewElLocation = "default";
  tgpInsertElement(newHeadline);
}
