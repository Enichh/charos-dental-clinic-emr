<?php

namespace CharosEMR\Presentation\Http\Responses;

class ViewRenderer
{
    private string $viewsPath;

    public function __construct(string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? __DIR__ . '/../../Views';
    }

    public function render(string $view, array $data = []): void
    {
        extract($data);

        ob_start();
        require $this->viewsPath . '/' . $view . '.php';
        $content = ob_get_clean();

        if (isset($layout)) {
            require $this->viewsPath . '/layouts/' . $layout . '.php';
        } else {
            echo $content;
        }
    }
}
