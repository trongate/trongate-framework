<?php 
function draw_child_nodes($parent_page_id, $all_pages) {

    $child_nodes = [];
    foreach ($all_pages as $page) {
        $node_data['id'] = $page->id;
        $node_data['page_title'] = $page->page_title;
        $priority = $page->priority;
        $node_parent_id = $page->parent_page_id;

        if ($node_parent_id == $parent_page_id) {
            //this node MUST be a child of the parent_page_id
            $child_nodes[$priority] = $node_data;
        }
    }

    ksort($child_nodes); //order by priority asc

    //now that we've gather our child nodes, let's display them
    foreach ($child_nodes as $child_node) {
        echo '<div id="record-id-'.$child_node['id'].'" class="node" draggable="true">';
        echo $child_node['page_title'];
        draw_child_nodes($child_node['id'], $all_pages); //draw child nodes for THIS node
        echo '</div>';
    }

}

draw_child_nodes(0, $all_pages);