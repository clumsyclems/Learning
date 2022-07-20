<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* pins/show.html.twig */
class __TwigTemplate_9bbb15096188cdbaac8c386fdb0039b8 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "pins/show.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "pins/show.html.twig"));

        // line 1
        echo "<!DOCTYPE>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <title>Pinterest-clone Show Pin n°";
        // line 5
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["pin"]) || array_key_exists("pin", $context) ? $context["pin"] : (function () { throw new RuntimeError('Variable "pin" does not exist.', 5, $this->source); })()), "id", [], "any", false, false, false, 5), "html", null, true);
        echo "</title>
</head>
</head>
<body>
    <h1>";
        // line 9
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["pin"]) || array_key_exists("pin", $context) ? $context["pin"] : (function () { throw new RuntimeError('Variable "pin" does not exist.', 9, $this->source); })()), "title", [], "any", false, false, false, 9), "html", null, true);
        echo "</h1>

    <p>";
        // line 11
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (isset($context["pin"]) || array_key_exists("pin", $context) ? $context["pin"] : (function () { throw new RuntimeError('Variable "pin" does not exist.', 11, $this->source); })()), "description", [], "any", false, false, false, 11), "html", null, true);
        echo "</p>

    <a href=\"";
        // line 13
        echo $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("app_home");
        echo "\"> Back </a>

</body>
</html>";
        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

    }

    public function getTemplateName()
    {
        return "pins/show.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  66 => 13,  61 => 11,  56 => 9,  49 => 5,  43 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!DOCTYPE>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <title>Pinterest-clone Show Pin n°{{pin.id}}</title>
</head>
</head>
<body>
    <h1>{{pin.title}}</h1>

    <p>{{pin.description}}</p>

    <a href=\"{{ path('app_home') }}\"> Back </a>

</body>
</html>", "pins/show.html.twig", "/home/crodriguez/Documents/GitHub/Learning/Symfony/pinterest-clone/templates/pins/show.html.twig");
    }
}
