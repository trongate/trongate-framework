<h1>Webpages - List View here</h1>

<div class="text-right accordion-controls">
  <button class="expand-all-btn" onclick="expandAll()">Expand All</button>
  <button class="close-all-btn" onclick="closeAll()">Close All</button>
</div>

<?php
function get_child_categories($parent_id, $rows) {
  $child_categories = [];
  foreach($rows as $row) {
    if($row->parent_page_id === $parent_id) {
      $child_categories[] = $row;
    }    
  }

  $num_child_categories = count($child_categories);
  if($num_child_categories<1) {
    $child_categories = false;
  }

  return $child_categories;
}

function build_sub_categories($parent_id, $rows) {
  // Establish what the sub categories are
  $sub_categories = get_child_categories($parent_id, $rows);
  if ($sub_categories !== false) {
    echo '<div class="subwebpage">';

    foreach ($sub_categories as $sub_webpage) {
      // Get the id of this subwebpage
      $sub_webpage_id = $sub_webpage->id;

      // Does this sub webpage have children?
      $child_categories = get_child_categories($sub_webpage_id, $rows);
      $got_children = ($child_categories === false) ? false : true;

      if ($got_children === false) {
        $webpage_url = BASE_URL.'trongate_pages/display/'.$sub_webpage->url_string;
        echo '<p><a href="'.$webpage_url.'">' . $sub_webpage->page_title . '</a></p>';
      } else {

        echo '<div class="webpage">';
        echo '<button class="accordion-btn">'.$sub_webpage->page_title.' <span class="plus">+</span></button>';
        build_sub_categories($sub_webpage->id, $rows);
        echo '</div>';
      }
    }

    // Close the subwebpage div
    echo '</div>';
  }
}
?>



<div class="accordion">
    <?php
    foreach($rows as $row) {
      if ($row->parent_page_id == 0) {
      ?>
    <div class="webpage">
      <button class="accordion-btn"><?= $row->page_title ?> <span class="plus">+</span></button>
      <?php
      build_sub_categories($row->id, $rows);
      ?>
    </div>
      <?php
    }   
  }
    ?>
</div>
<style>
.accordion {
  color:  #000;
  background-color: white;
  margin-top: 2em !important;
}

.accordion-btn {
  background-color: steelblue;
  color: #eee;
  cursor: pointer;
  width: 100%;
  text-align: left;
  border: none;
  outline: none;
  transition: 0.4s;
  border-radius: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: bold;
}

.active,
.accordion-btn:hover {
  background-color: steelblue;
  border: none;
  color: #fff;
}

.accordion,
.accordion-btn,
.subwebpage {
  font-size: 16px;
  margin: 0;
}

.accordion-btn,
.subwebpage,
.subwebpage p {
  padding: 7px 12px;
}

.subwebpage p {
  line-height: unset;
  margin: 0;
  font-size: 15px;
}

.webpage {
  border-left:  1px steelblue solid;
  border-right:  1px steelblue solid;
  border-bottom: 1px #376b97 solid;
}

.subwebpage > p {
  background-color: #fff;
  border:  1px #e9e9e9 solid;
}

.subwebpage > p:nth-child(even) {
  border-top:  1px #f8f8fb solid;
  border-bottom:  1px #f8f8fb solid;
  background-color: #f9f9fa;
}

.subwebpage {
  background-color: #e8edf2;
  display: none;
  overflow: hidden;
}

.plus, .minus {
  font-size: 24px;
}

.plus {
  float: right;
}

.minus {
  float: right;
}
</style>

<script>
const plusIcons = document.querySelectorAll('.plus');
const accordion = document.querySelector('.accordion');

plusIcons.forEach(icon => {
  icon.addEventListener('click', () => {
    const btn = icon.parentNode;
    const subwebpage = btn.nextElementSibling;
    const plusMinus = btn.querySelector('.plus');
    if (subwebpage.style.display === 'block') {
      subwebpage.style.display = 'none';
      plusMinus.innerHTML = '+';
      plusMinus.classList.remove('minus');
    } else {
      subwebpage.style.display = 'block';
      plusMinus.innerHTML = '-';
      plusMinus.classList.add('minus');
    }
  });
});

accordion.addEventListener('click', (event) => {
  if (event.target.classList.contains('minus') && event.target.parentNode.classList.contains('webpage')) {
    const subcategories = event.target.parentNode.nextElementSibling.querySelectorAll('.subwebpage');
    subcategories.forEach(subwebpage => {
      subwebpage.style.display = 'none';
      const plusMinus = subwebpage.previousElementSibling.querySelector('.plus');
      plusMinus.innerHTML = '+';
      plusMinus.classList.remove('minus');
    });
  }
});

function expandAll() {
    const plusIcons = document.querySelectorAll('.plus');
    for (var i = plusIcons.length - 1; i >= 0; i--) {
      plusIcons[i].click();
    }
}

function closeAll() {
    const minusIcons = document.querySelectorAll('.minus');
    for (var i = minusIcons.length - 1; i >= 0; i--) {
      minusIcons[i].click();
    }
}
</script>