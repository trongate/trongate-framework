<style>
.pagination {
    display: inline-block;
    margin-top: 1em;
}

.pagination:first-of-type {
    margin: 0 0 1em 0;
}

.pagination a {
    color: black;
    float: left;
    padding: 8px 16px;
    text-decoration: none;
    border: 1px solid #ddd;
}

.pagination a.active {
    background-color: var(--primary);
    color: white;
    border: 1px solid var(--primary);
}

.pagination a:hover:not(.active) {
    background-color: #fff;
    color: var(--primary);
}

.pagination a:first-child {
    border-top-left-radius: 5px;
    border-bottom-left-radius: 5px;
}

.pagination a:last-child {
    border-top-right-radius: 5px;
    border-bottom-right-radius: 5px;
}

.tg-showing-statement {
    color: #666;
    margin-bottom: 1em;
}

@media screen and (max-width: 550px) {
    .pagination a {
        padding: 10px 14px;
    }
}
</style>