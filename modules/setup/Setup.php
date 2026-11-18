<?php
class Setup {

    public function run() {
        $base_url = $this->get_assumed_base_url();
        require('views/setup_template.php');
    }

    /**
     * Draw the step indicator HTML for the setup wizard.
     *
     * @param int $current_step The current active step (1-5).
     * @return string The rendered steps indicator HTML.
     */
    private function draw_steps_indicator(int $current_step) {
        $steps = [
            1 => 'Welcome',
            2 => 'Base URL',
            3 => 'Database',
            4 => 'Admin',
            5 => 'Finalize'
        ];

        $html = '<div class="steps">';

        foreach ($steps as $num => $title) {
            $active_class = ($num === $current_step) ? ' active' : '';
            $html .= '<div class="step' . $active_class . '">';
            $html .= '<span class="step-number">' . $num . '</span>';
            $html .= '<span class="step-title">' . $title . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
        echo $html;
    }

    private function get_assumed_base_url(): string {
        $url = explode('?', current_url(), 2)[0]; // drop the query string first
        return rtrim($url, '/') . '/';
    }

}