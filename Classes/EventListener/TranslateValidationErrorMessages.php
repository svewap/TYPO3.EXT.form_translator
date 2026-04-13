<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent;

#[AsEventListener(identifier: 'form_translator/translate-validation-error-messages')]
final class TranslateValidationErrorMessages
{
    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory
    ) {}

    public function __invoke(AfterFormIsBuiltEvent $event): void
    {
        $form = $event->form;
        $this->processRenderable($form);
    }

    private function processRenderable(RenderableInterface $renderable): void
    {
        if ($renderable instanceof AbstractFormElement) {
            $this->translateValidationErrorMessages($renderable);
        }

        if (method_exists($renderable, 'getRenderables')) {
            foreach ($renderable->getRenderables() as $child) {
                $this->processRenderable($child);
            }
        }
    }

    private function translateValidationErrorMessages(AbstractFormElement $renderable): void
    {
        $validationErrorMessages = $renderable->getProperties()['validationErrorMessages'] ?? [];
        if (empty($validationErrorMessages)) {
            return;
        }

        $validationErrorMessages = array_map(fn($message) => $this->translate($message, $renderable), $validationErrorMessages);
        $renderable->setProperty('validationErrorMessages', $validationErrorMessages);
    }

    private function translate(array $message, AbstractFormElement $renderable): array
    {
        $form = $renderable->getRootForm();
        $id = str_replace([
            '<form-identifier>',
            '<element-identifier>',
            '<error-code>',
        ], [
            $form->getRenderingOptions()['_originalIdentifier'],
            $renderable->getIdentifier(),
            $message['code'],
        ], '<form-identifier>.validation.error.<element-identifier>.<error-code>');

        $translationFiles = $form->getRenderingOptions()['translation']['translationFiles'] ?? [];
        $translationService = $this->getLanguageService();
        foreach ($translationFiles as $translationFile) {
            $translationService->includeLLFile($translationFile);
            $input = 'LLL:' . $translationFile . ':' . $id;
            $label = $translationService->sL($input);
            if ($label && $label !== $input) {
                $message['message'] = $label;
                break;
            }
        }

        return $message;
    }

    private function getLanguageService(): LanguageService
    {
        $siteLanguage = $this->getRequest()->getAttribute('language');
        return $this->languageServiceFactory->createFromSiteLanguage($siteLanguage);
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}