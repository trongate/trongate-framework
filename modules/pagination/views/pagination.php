<?php
if ($include_showing_statement && $total_rows > 0) {
    $showing_start = (($current_page - 1) * $limit) + 1;
    $showing_end = min($current_page * $limit, $total_rows);
    echo '<p class="showing-statement">Showing ' . $record_name_plural . ' ' . number_format($showing_start) . ' to ' . number_format($showing_end) . ' of ' . number_format($total_rows) . '.</p>';
}

if ($total_pages > 1) {
    echo '<nav class="pagination">';
    echo '<ul class="pagination-list">';
    
    foreach ($pagination_links as $link) {
        if ($link['type'] === 'ellipsis') {
            echo '<li class="pagination-ellipsis"><span>' . $link['label'] . '</span></li>';
        } elseif ($link['type'] === 'prev') {
            echo '<li class="pagination-prev"><a href="' . $link['url'] . '">' . $link['label'] . '</a></li>';
        } elseif ($link['type'] === 'next') {
            echo '<li class="pagination-next"><a href="' . $link['url'] . '">' . $link['label'] . '</a></li>';
        } else {
            $class = $link['is_current'] ? 'pagination-link active' : 'pagination-link';
            echo '<li><a href="' . $link['url'] . '" class="' . $class . '">' . $link['label'] . '</a></li>';
        }
    }
    
    echo '</ul>';
    echo '</nav>';
}
?>

<style>
.showing-statement {
    margin-bottom: 1em;
    color: #666;
}

.pagination {
    margin: 2em 0;
}

.pagination-list {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 0.25em;
}

.pagination-list li {
    display: inline-block;
}

.pagination-link,
.pagination-prev a,
.pagination-next a {
    display: block;
    padding: 0.5em 0.75em;
    border: 1px solid #ddd;
    background-color: #fff;
    color: #333;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-link:hover,
.pagination-prev a:hover,
.pagination-next a:hover {
    background-color: #f5f5f5;
    border-color: #999;
}

.pagination-link.active {
    background-color: var(--primary, #007bff);
    color: #fff;
    border-color: var(--primary, #007bff);
    pointer-events: none;
}

.pagination-ellipsis span {
    display: block;
    padding: 0.5em 0.75em;
    color: #999;
}
</style>