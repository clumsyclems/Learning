<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Repository\PinRepository;
use Doctrine\ORM\EntityManagerInterface;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PinsController extends AbstractController
{
    /*
    //meta donnée pour l'appel du controller ici c'est si on appel /pins alors renvoyer ce fichier json
    #[Route('/pins', name: 'pins')]
    //action effectuer par le controller, on peut en créer plusieur pour un même controler
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PinsController.php',
        ]);
    }
    */

    #[Route('/', name:'app_home')]
    /**
     * ancien moyen de données des info sur le controller:
     * @Route("/", name="app_home")
     */
    /*
    //l'utilisation de ManagerRegistry est pour les versions de symfony supérieur à la 5.4
    //l'utilisation de EntityManagerInterface est pour les versions de symfony inférieur à la 5.4
    //l'utilisation de PinRepository permet de simplifier le code lors de la récupération du repo
    */
    public function index(ManagerRegistry $doctrine, EntityManagerInterface $em, PinRepository $repo): Response //Afin de prévenir de tout problem de renvoie de donnée l'ajout : Response annonce de manière explicite que la fonction doirt renvoyer une variable de type reponse
    {
            /*
            //httpfoundation souhaite que l'on renvoie un objet de type réponse
            return new Response("<h1> Hello World </h1>");
            */
            
            //Afin d'afficher une page de manière plus efficace et conventionelle on va utiliser l'outil render de AbstractController

            //objet créé toujours en mémoire cache mais pas enregistrer dans la bdd
            //commenter pour essayer les repository
        /*  $pin = new Pin;
            $pin -> setTitle('Title 4');
            $pin -> setDescription('Description 4');
        */    
            //Pour pouvoir rendre cette variable perseitente dans le serveur il alors utiliser cette ligne de commande: (avant 5.4)
            /**
             * $this -> em->persist($pin); //expliquer au manager que l'on veut stocker cette valeur
             * $this -> em->flush(); //le manager va stoker la valeur dans la base de données
            */

            //Pour pouvoir rendre cette variable perseitente dans le serveur il alors utiliser cette ligne de commande: (après 5.4)
            //commenter pour essayer les repository
        /*    $doctrine -> getManager() -> persist($pin); //expliquer au manager que l'on veut stocker cette valeur
              $doctrine -> getManager() -> flush(); //le manager va stoker la valeur dans la base de données
        */
                    
            //var_dump($pin); //affichage un peu moche
            //dump($pin); //affichage plus esthetique mais die(); est toujours utilisé
            //dd($pin); // affichage esthetique et plus de die()
            //die(); //arret du script
        
        //Utilisation des repository
        /*Il existe une façon plus efficace
        $repo = $em -> getRepository(Pin::class); //Pin::class permet à symfony de récuperer le chemin où se situe le repository de la classe Pin

        $pins = $repo -> findAll();
        */

        //return $this->render('pins/index.html.twig' , ['pins' => $pins ]);
        //return $this->render('pins/index.html.twig' , compact('pins')); //au lieu de devoir affecter chaque variable d'un tableau à la valeur pin,la fonction compact() va le faire plus efficacement
        
        //version plus efficace
        return $this->render('pins/index.html.twig' , ['pins' => $repo -> findAll()]);
    }

    #[Route('/pins/{id<[0-9]+>}', name:"app_pin_show")]
    // Afin que le choix soit dynamique en fonction de l'id on le change par un tag que l'on ajoute dans la fonction du controller
    //Ajout du regex des int accoler au tag afin de ne pas avoir de confusion avec la page /pins/create
    //Attention à la notation
    
    
    /*
        Methode trop répétitive donc on va obtimiser tout ça avec le code qui va suivre celui ci

    public function show(PinRepository $repo, int $id): Response //cast du i$id pour ne pas avoir de valeur aléatoire
    {
        $pin = $repo->find($id);

        //Code erreur info sur le site : https://httpstatuses.com/ 
        //Pour éviter les erreurs venant du serveur de type 5xx et renvoyer une 404 e cas d'erreur de page
        if (!$pin) //si $pin n'existe pas
        {
            throw $this -> createNotFoundException('Pin #' . $id . ' not found');
        }
        return $this->render('pins/show.html.twig', compact('pin'));
    }
    */

    public function show(Pin $pin): Response //cast du i$id pour ne pas avoir de valeur aléatoire
    {
        
        return $this->render('pins/show.html.twig', compact('pin'));
    }

    #[Route('/pins/create' , name:'app_pins_create', methods: ['GET', 'POST'])]
    public function create(Request $request, ManagerRegistry $doctrine) : Response// permet d'avoir des informations sur la requête effectué
    {
        /* manière conventionelle de faire un formulaire maintenant symfony peut faire mieux

            if($request->isMethod('POST')){
                //récuperation des données 
                //pour un post:
                $data = $request->request->all(); 

                //création de l'instance qui va récuperer les données
                $pin = new Pin;
                $pin -> setTitle($data['title']);
                $pin -> setDescription($data['description']);

                //stockage de la données dans la bdd
                $doctrine -> getManager() -> persist($pin); //expliquer au manager que l'on veut stocker cette valeur
                $doctrine -> getManager() -> flush(); //le manager va stoker la valeur dans la base de données

                //habituellement après un post on redirige l'utilisateur vers une autre page 
                //de la même manière que lors de l'appel du controller pour rediriger vers une autre page il est préférable d'utiliser le nom du route qui lui est associé avec "generateurl()'

                //mais il existe une meilleur maniere de la faire que cette ligne 
                //return $this->redirect($this -> generateurl('app_home'));
                //redirect permet de rediriger vers n'importe quelle url (https://google.com par exemple)
                //Celle ci : 
                return $this->redirectToRoute('app_home');

            }
            else if ($request->isMethod('GET')){
                // récuperation des données 
                //pour un get : 
                //($request->query);
            }
        */

        //pour créer un formulaire avec symfony
        
        //possibilité de donner information pré créer pour remplir le formulaire à l'avance 

            //on peut insérer un tableau avec les mêmes instances que le formulaire :
                //$data = ['title' => 'Cool, 'description' => 'pas cool']
            
            //On peut aussi lieu donner un objet qui à des attribut associé au formulaire
            //Bien plus avantageux pour la création de formulaire
            $pin = new Pin;
            $pin -> setTitle('Cool');
            $pin -> setDescription('Pas cool');
                

        $form = $this->createFormBuilder(/*$data*/ $pin) //il faut que les champs donnée en parametre ai tout les attribut annoncé dans le formulaire
               
                //Pour pouvoir avoir les different type possible pour un formulaire symfony aller voir sur le site : https://symfony.com/doc/current/reference/forms/types.html
                //on peut ajouter des attribut html directement à nos objet dans le formulaire lire la doc ci-dessus

                //Comme on envoie à la création du formulaire un objet à remplir, symfony peut à l'avance déterminer les types de nos attribut
                //grace au mapping dans le fichier Pin.php ici à la place des classes on peut y ajouter la variable null et symfony s'occupe du reste

                -> add('title',null , [ // ajout des attributs que l'on veut mettre dans notre formulaire.
                    'attr' => [
                        'class' => 'title', 
                        'autofocus' => true
                        ]
                    ]
                )             
                -> add('description', null, ['attr' => ['class' => 'description', 'row' => 10, 'cols' => 50]]) // on peut ajouter comme second argument le type de l'objet ajouter
                //supression du bouton car il es préférable de le mettre directement sur la page utilisé
                //->add('submit', SubmitType::class) //dans la documentation ci-dessus il est aussi donné tout les attribut possible pour chaque objet dans le formulaire 
                -> getForm()                 // création du formulaire 
        
        ;
        
        //maintenant qu'on a fait notre formulaire il ne reste plus qu'à nous occuper de son intéraction avec la bdd
        
        $form -> handleRequest($request); //cette méthode permet de pouvoir récuperer toute les données qu'on lui a donné si elle capte un post

        if ($form->isSubmitted() && $form->isValid()) { //verification s'il y a un submit lors de la requete et si la requete est valide au exigence 
            //on push les donnée dans la bdd
            //$data = $form->getData(); //récupération des données dans le formulaire
            //dans le cas où on utilise un objet lors de la creation du formulaire le $form->getData(); renvoie ce même objet et donc plus besion de data 

            //il est aussi possible de récuperer des info précise dans le formulaire comme le title ou la description
            // exemple : $form->get('description')->getData(); pour seulement la description

            //Déjà instancié avant la fonction
                //création de la nouvelle entité
                //$pin = new Pin;
                //$pin -> setTitle($data['title']);
                //$pin -> setDescription($data['description']);

            //stockage de la données dans la bdd
            //$doctrine -> getManager() -> persist($form->getData()); //expliquer au manager que l'on veut stocker cette valeur
            
            //il est possible d'utiliser directement l'objet utiliser pour le formulaire car symfony l'a déà setter au moment du post
            $doctrine -> getManager() -> persist($pin);
            $doctrine -> getManager() -> flush(); //le manager va stoker la valeur dans la base de données

            //Maintenant on souhaite pouvoir rediriger vers la page du pin créer
            //return $this->redirectToRoute('app_home');

            return $this->redirectToRoute('app_pin_show', ['id' => $pin->getId()]);
            
        }

        
        //ici on renvoie un formulaire brut mais on veut une version visible pour le twig donc on va ajouter la fonction "createView()"
        //return $this->render('pins/create.html.twig', ['monFormulaire' => $form]); 
        return $this->render('pins/create.html.twig', [ 'monFormulaire' => $form->createView() ]); 
    }


}
