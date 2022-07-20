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

/* pins/create.html.twig */
class __TwigTemplate_a93326628eb962538b651cccb94a53eb extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "pins/create.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "pins/create.html.twig"));

        // line 1
        echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <title>Pinterest-clone</title>
</head>
<body>
    <h1>Create Pin</h1>
    
    <!-- Manière conventionnel de faire un formulaire mais symfony peut faire mieux

    <form method=\"POST\">
        <div>
            <label for=\"title\">Title</label>
            <input type=\"text\" name=\"title\" id=\"title\" required>  ";
        // line 16
        echo "        </div>

        <div>
            <label for=\"title\">Description</label><br>
            <textarea id = \"description\" name=\"description\" rows=\"5\" cols=\"50\" required></textarea>
        </div>

        <input type=\"submit\" value=\"Create Pin\">
    </form>
    -->
    ";
        // line 27
        echo "    ";
        // line 28
        echo "
    ";
        // line 30
        echo "    ";
        echo         $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->renderBlock((isset($context["monFormulaire"]) || array_key_exists("monFormulaire", $context) ? $context["monFormulaire"] : (function () { throw new RuntimeError('Variable "monFormulaire" does not exist.', 30, $this->source); })()), 'form_start');
        echo " ";
        // line 31
        echo "        ";
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock((isset($context["monFormulaire"]) || array_key_exists("monFormulaire", $context) ? $context["monFormulaire"] : (function () { throw new RuntimeError('Variable "monFormulaire" does not exist.', 31, $this->source); })()), 'widget');
        echo "  ";
        // line 32
        echo "
        <input type=\"submit\" value=\"Create Pin\"> ";
        // line 34
        echo "    ";
        echo         $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->renderBlock((isset($context["monFormulaire"]) || array_key_exists("monFormulaire", $context) ? $context["monFormulaire"] : (function () { throw new RuntimeError('Variable "monFormulaire" does not exist.', 34, $this->source); })()), 'form_end');
        echo " ";
        // line 35
        echo "
</body>
</html>";
        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

    }

    public function getTemplateName()
    {
        return "pins/create.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  91 => 35,  87 => 34,  84 => 32,  80 => 31,  76 => 30,  73 => 28,  71 => 27,  59 => 16,  43 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <title>Pinterest-clone</title>
</head>
<body>
    <h1>Create Pin</h1>
    
    <!-- Manière conventionnel de faire un formulaire mais symfony peut faire mieux

    <form method=\"POST\">
        <div>
            <label for=\"title\">Title</label>
            <input type=\"text\" name=\"title\" id=\"title\" required>  {#required est utilisé pour pouvoir demander obligatoirement un titre avant de post#}
        </div>

        <div>
            <label for=\"title\">Description</label><br>
            <textarea id = \"description\" name=\"description\" rows=\"5\" cols=\"50\" required></textarea>
        </div>

        <input type=\"submit\" value=\"Create Pin\">
    </form>
    -->
    {#Peu utile car par de bouton comme on l'a supprimer dans le formulaire envoyer en argument#}
    {#{{ form(monFormulaire) }} {#Fonction twig pour convertir un formulaire en string #}

    {#Manière plus agréable à faire :#}
    {{form_start(monFormulaire)}} {#Début du formulaire#}
        {{ form_widget(monFormulaire)}}  {#Champs demander pour la création du formulaire#}

        <input type=\"submit\" value=\"Create Pin\"> {#boutton appartenant au formulaire pour la requete#}
    {{form_end(monFormulaire)}} {#Fin du formulaire#}

</body>
</html>", "pins/create.html.twig", "/home/crodriguez/Documents/GitHub/Learning/Symfony/pinterest-clone/templates/pins/create.html.twig");
    }
}
