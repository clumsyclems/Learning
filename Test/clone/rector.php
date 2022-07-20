<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Nette\Set\NetteSetList;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Rector\MethodCall\SwiftCreateMessageToNewEmailRector;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src'
    ]);

    // Define what rule sets will be applied
    $containerConfigurator->import(LevelSetList::UP_TO_PHP_80);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);

    $containerConfigurator->import(DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES);
    $containerConfigurator->import(SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES);
    $containerConfigurator->import(NetteSetList::ANNOTATIONS_TO_ATTRIBUTES);
    $containerConfigurator->import(SensiolabsSetList::FRAMEWORK_EXTRA_61);

    $services = $containerConfigurator->services();

    $services->set(SwiftCreateMessageToNewEmailRector::class);

    $services->set(RenameClassRector::class)
        ->configure([
            'Swift_Mailer' => 'Symfony\Component\Mailer\MailerInterface',
            'Swift_Message' => 'Symfony\Component\Mime\Email',
            // message
            'Swift_Mime_SimpleMessage' => 'Symfony\Component\Mime\RawMessage',
            // transport
            'Swift_SmtpTransport' => 'Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport',
            'Swift_FailoverTransport' => 'Symfony\Component\Mailer\Transport\FailoverTransport',
            'Swift_SendmailTransport' => 'Symfony\Component\Mailer\Transport\SendmailTransport',
        ]);
};

