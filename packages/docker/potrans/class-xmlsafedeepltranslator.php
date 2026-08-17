<?php

namespace potrans\translator;

use DeepL\DeepLException;
use DeepL\Translator as DeepLApiTranslator;
use Gettext\Loader\PoLoader;
use Gettext\Translation;
use Gettext\Translations;
use Symfony\Component\Console\Input\InputInterface;

/**
 * There are two global variables $input and $output
 *
 * potrans's stock DeepLTranslator escapes text with htmlentities() before sending it to DeepL with
 * `tag_handling: xml`. htmlentities() emits named HTML entities (eg. "…" -> "&hellip;") that are not
 * among XML's five predefined entities (&amp; &lt; &gt; &apos; &quot;), so DeepL's XML parser rejects
 * them with "Tag handling parsing failed ... undefined entity". This translator escapes only the
 * characters that are actually reserved in XML, leaving everything else - including typographic
 * characters like "…" - untouched, since UTF-8 text content doesn't need entity-encoding in XML.
 *
 * Usage: pass --translator=packages/docker/potrans/class-xmlsafedeepltranslator.php to `potrans deepl`
 *
 * @var InputInterface $input
 * @see \potrans\commands\DeepLTranslatorCommand for more information
 */
class XmlSafeDeepLTranslator extends TranslatorAbstract
{
  public function __construct(
    private DeepLApiTranslator $translator,
    private ?Translations $pot = null,
    private ?string $regex = null,
  ) {
  }

  /**
   * @throws DeepLException
   */
  public function getTranslation(Translation $sentence): string
  {
    $text = $this->pot?->find($sentence->getContext(), $sentence->getOriginal())?->getTranslation() ?: $sentence->getOriginal();
    $text = \htmlspecialchars($text, ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');

    $response = $this->translator->translateText(
      $this->regex ? preg_replace('/(' . $this->regex . ')/', '<keep>$1</keep>', $text) : $text,
      $this->from,
      $this->to,
      [
        'tag_handling' => 'xml',
        'ignore_tags'  => 'keep',
      ]
    );

    return \htmlspecialchars_decode(preg_replace('/<\/?keep>/i', '', $response->text), ENT_XML1 | ENT_SUBSTITUTE);
  }
}

$apikey = $_ENV['DEEPL_API_KEY'] ?? (string) $input->getOption('apikey');

$pot_file = $input->getOption('pot');
$pot      = $pot_file && file_exists($pot_file) ? (new PoLoader())->loadFile($pot_file) : null;

/**
 * Return custom translator instance
 */
return new XmlSafeDeepLTranslator(
  new DeepLApiTranslator($apikey),
  $pot,
  $input->getOption('ignore'),
);
