<?php
/**
 * This class represents the Next Best Action (NBA) model.
 */

namespace ionos_wordpress\essentials\dashboard\blocks\next_best_actions\model;

/**
 * Class NBA
 */
class NBA {
    /**
     * ID of the NBA
     *
     * @var string
     */
    private $id = '';

    /**
     * Title of the NBA
     *
     * @var string
     */
    private $title = '';

    /**
     * Description of the NBA
     *
     * @var string
     */
    private $description = '';

    /**
     * Image url of the NBA
     *
     * @var string
     */
    private $image = '';

    /**
     * Link of the NBA
     *
     * @var string
     */
    private $link = '';

    /**
     * Callback function of the NBA
     *
     * @var string
     */
    private $callback = '';

    /**
     * NBA constructor.
     *
     * @param string $id
     * @param string $title
     * @param string $description
     * @param string $image
     * @param string $link
     * @param string $callback
     */
    public function __construct( $id, $title, $description, $image, $link, $callback ) {
        $this->id           = $id;
        $this->title        = $title;
        $this->description  = $description;
        $this->image        = $image;
        $this->link         = $link;
        $this->callback     = $callback;
    }

    /**
     * Get ID.
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function get_image() {
        return $this->image;
    }

    /**
     * Get link.
     *
     * @return string
     */
    public function get_link() {
        return $this->link;
    }

    /**
     * Get callback.
     *
     * @return string
     */
    public function get_callback() {
      if ( is_callable( $this->callback ) ) {
        $result = call_user_func( $this->callback );

        if ( is_bool( $result ) ) {
          return $result;
        }
      }

      return false;
    }
}
