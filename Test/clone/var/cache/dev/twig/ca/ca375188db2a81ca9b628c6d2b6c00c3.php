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

/* pins/index.html.twig */
class __TwigTemplate_d4940c400180137ae56f9f418d875313 extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "pins/index.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "pins/index.html.twig"));

        // line 1
        echo "<!DOCTYPE>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <title>Pinterest-clone</title>
</head>
<body>
    <h1>All Pins</h1>

    ";
        // line 11
        echo "
    <a href=\" ";
        // line 12
        echo $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("app_pins_create");
        echo "\" target=\"\"> Create Pin </a> 
    
    ";
        // line 15
        echo "    
    ";
        // line 17
        echo "    ";
        // line 18
        echo "    
    ";
        // line 20
        echo "
        ";
        // line 21
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["pins"]) || array_key_exists("pins", $context) ? $context["pins"] : (function () { throw new RuntimeError('Variable "pins" does not exist.', 21, $this->source); })()));
        $context['_iterated'] = false;
        foreach ($context['_seq'] as $context["_key"] => $context["pin"]) {
            echo " ";
            // line 22
            echo "            <article>
                <h1> 
                    ";
            // line 28
            echo "                    <a href=\"";
            echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("app_pin_show", ["id" => twig_get_attribute($this->env, $this->source, $context["pin"], "id", [], "any", false, false, false, 28)]), "html", null, true);
            echo "\"> ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["pin"], "getTitle", [], "method", false, false, false, 28), "html", null, true);
            echo " </a> 
                </h1> ";
            // line 30
            echo "               
                <p>";
            // line 31
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["pin"], "getDescription", [], "method", false, false, false, 31), "html", null, true);
            echo "</p>";
            // line 32
            echo "            </article>
        ";
            $context['_iterated'] = true;
        }
        if (!$context['_iterated']) {
            // line 33
            echo " ";
            // line 34
            echo "            <p>Sorry, None pins in our database</p>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['pin'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 36
        echo "    
    ";
        // line 44
        echo "</body>";
        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

    }

    public function getTemplateName()
    {
        return "pins/index.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  114 => 44,  111 => 36,  104 => 34,  102 => 33,  96 => 32,  93 => 31,  90 => 30,  83 => 28,  79 => 22,  73 => 21,  70 => 20,  67 => 18,  65 => 17,  62 => 15,  57 => 12,  54 => 11,  43 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!DOCTYPE>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\" />
    <title>Pinterest-clone</title>
</head>
<body>
    <h1>All Pins</h1>

    {#Pour faciliter le changement des page il est préférable d'utiliser la fonction twig path('nom_route_du_controller')#}

    <a href=\" {{path('app_pins_create')}}\" target=\"\"> Create Pin </a> 
    
    {# Pour afficher une variable venant du controller ici PinController doit utiliser la commande : {{<nom_de_la_variable>}} #}
    
    {#Pour voir tout les filtre de twig : https://twig.symfony.com/doc/3.x/filters/index.html #}
    {#{%if pins|length > 0%}{#ici utilisation du filtre length#}
    
    {#Pour avoir plus d'information au sujet des commandes twig: https://twig.symfony.com/doc #}

        {% for pin in pins %} {# Pour utilisation d'une logique on utilse {% %} sinon pour un appel de variable on tape {{}} #}
            <article>
                <h1> 
                    {#
                        Permet de récuperer le chemin vers le route qui va nous permettre de récupérer les valeurs et
                        path admet comme d'autre argument ce qu'il doit transmettre comme variable si le chemin dépends d'un attribut spé
                    #}
                    <a href=\"{{ path('app_pin_show' , {id : pin.id} )}}\"> {{ pin.getTitle() }} </a> 
                </h1> {# On peut aussi utiliser les variables même des class ici pin.title et pin.description#}
               
                <p>{{ pin.getDescription() }}</p>{# Et twig s'occupera lui même d'appeler les getters et les setter et pas besoin des parentheses#}
            </article>
        {%else%} {#Il est possible d'utiliser un else dans un for ici il stipule que s'il n'y a pas de pin alors fait ce qui suit#}
            <p>Sorry, None pins in our database</p>
        {% endfor %}
    
    {#
    {% else %}
    
        <p>Sorry, None pins in our database</p>
    
    {% endif %}
    #}
</body>", "pins/index.html.twig", "/home/crodriguez/Documents/GitHub/Learning/Symfony/pinterest-clone/templates/pins/index.html.twig");
    }
}
