<?php
/**
 * Pagination rendering class for generating navigation controls and record statements.
 * Provides configurable pagination links with accessibility support and optional styling.
 */
class Pagination extends Trongate {

    /**
     * Display pagination controls and optional showing statement.
     *
     * @param array $pagination_data Configuration array
     * @return void
     */
    public function display(array $pagination_data): void {

        // Get validated and processed data from model
        $data = $this->model->prepare_pagination_data($pagination_data);
        
        // Early return if no pagination needed
        if ($data === null) {
            return;
        }
        
        // Output showing statement if enabled
        if ($data['include_showing_statement']) {
            echo $this->get_showing_statement($data);
        }
        
        // Render pagination HTML
        $this->render_pagination($data);
    }

    /**
     * Generate the showing statement for pagination.
     *
     * @param array $data Processed pagination data
     * @return string The showing statement HTML
     */
    private function get_showing_statement(array $data): string {
        $offset = ($data['current_page'] - 1) * $data['limit'];
        $start = $offset + 1;
        $end = min($offset + $data['limit'], $data['total_rows']);
        $total = number_format($data['total_rows']);
        
        // If custom showing_statement provided, use it with placeholders
        if (isset($data['showing_statement']) && !empty($data['showing_statement'])) {
            $statement = str_replace(
                ['{start}', '{end}', '{total}'],
                [$start, $end, $total],
                $data['showing_statement']
            );
        } else {
            // Default English statement
            $statement = 'Showing ' . $start . ' to ' . $end . ' of ' . $total . ' ' . $data['record_name_plural'] . '.';
        }
        
        return '<p class="tg-showing-statement">' . $statement . '</p>' . PHP_EOL;
    }

    /**
     * Render pagination links based on provided pagination data.
     *
     * @param array $data Processed pagination data
     * @return void
     */
    private function render_pagination(array $data): void {
        $html = PHP_EOL . $data['settings']['pagination_open'] . PHP_EOL;

        $max_links = $data['num_links_per_page'];  // NOW CONFIGURABLE
        $num_links_to_side = (int) floor($max_links / 2);
        
        $current_page = (int) $data['current_page'];
        $total_pages = (int) $data['total_pages'];
        $pagination_root = $data['pagination_root'];
        $settings = $data['settings'];

        // First and Previous links (only if not on page 1)
        if ($current_page > 1) {
            $html .= $settings['first_link_open'];
            $html .= $this->build_link(1, $pagination_root, $settings['first_link'], $settings['first_link_aria_label']);
            $html .= $settings['first_link_close'] . PHP_EOL;

            $html .= $settings['prev_link_open'];
            $html .= $this->build_link($current_page - 1, $pagination_root, $settings['prev_link'], $settings['prev_link_aria_label']);
            $html .= $settings['prev_link_close'] . PHP_EOL;
        }

        // Calculate range of pages to show
        $start_page = (int) max(1, $current_page - $num_links_to_side);
        $end_page = (int) min($total_pages, $current_page + $num_links_to_side);

        // Adjust start if we're near the end
        if ($end_page - $start_page < $max_links - 1) {
            $start_page = (int) max(1, $end_page - $max_links + 1);
        }

        // Page numbers
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i === $current_page) {
                $html .= $settings['cur_link_open'];
                $html .= $i;
                $html .= $settings['cur_link_close'] . PHP_EOL;
            } else {
                $html .= $settings['num_link_open'];
                $html .= $this->build_link($i, $pagination_root, (string) $i);
                $html .= $settings['num_link_close'] . PHP_EOL;
            }
        }

        // Next and Last links (only if not on last page)
        if ($current_page < $total_pages) {
            $html .= $settings['next_link_open'];
            $html .= $this->build_link($current_page + 1, $pagination_root, $settings['next_link'], $settings['next_link_aria_label']);
            $html .= $settings['next_link_close'] . PHP_EOL;

            $html .= $settings['last_link_open'];
            $html .= $this->build_link($total_pages, $pagination_root, $settings['last_link'], $settings['last_link_aria_label']);
            $html .= $settings['last_link_close'] . PHP_EOL;
        }

        $html .= $settings['pagination_close'] . PHP_EOL;

        // Add CSS if requested
        if ($data['include_css'] === true) {
            $html .= $this->get_default_css();
        }

        echo $html;
    }

    /**
     * Build a pagination link.
     *
     * @param int $page Page number
     * @param string $pagination_root Base URL for pagination
     * @param string $label Link label/text
     * @param string|null $aria_label Optional ARIA label for accessibility
     * @return string The HTML link
     */
    private function build_link(int $page, string $pagination_root, string $label, ?string $aria_label = null): string {
        // For page 1, use the root URL without the page number
        if ($page === 1) {
            $url = BASE_URL . rtrim($pagination_root, '/');
        } else {
            $url = BASE_URL . $pagination_root . $page;
        }
        
        $aria = $aria_label ? ' aria-label="' . htmlspecialchars($aria_label, ENT_QUOTES, 'UTF-8') . '"' : '';
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $aria . '>' . $label . '</a>';
    }

    /**
     * Generate default CSS for pagination.
     *
     * @return string The CSS code wrapped in style tags
     */
    private function get_default_css(): string {
        return $this->view('default_css', [], true);
    }
}