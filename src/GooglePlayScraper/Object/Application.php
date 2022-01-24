<?php
/*
* This file is part of the GooglePlayScraper package.
*
* (c) Smarter Solutions <contacto@smarter.com.ve>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace SmarterSolutions\PhpTools\GooglePlayScraper\Object;

use PHPTools\PHPHtmlDom\PHPHtmlDom;

/**
*
*/
class Application
{
   /**
   * Application identifier.
   * @var string
   */
   public $identifier;
   /**
   * Application url.
   * @var string
   */
   public $url;
   /**
   * Application Name
   * @var string
   */
   public $name;
   /**
   * Application Summary.
   * @var string
   */
   public $summary;
   /**
   * Application description.
   * @var string
   */
   public $description;
   /**
   * Application icon.
   * @var string
   */
   public $icon;
   /**
   * Number of downloads.
   * @var array
   */
   public $downloads = [];
   /**
   * Number of stars that have the application.
   * @var float
   */
   public $score = 0;
   /**
   * Number of app ratings.
   * @var integer
   */
   public $reviews = 0;
   /**
   * Star Ratings Detail.
   * @var array
   */
   public $histogram = [];
   /**
   * Last update.
   * @var string
   */
   public $updated;
   /**
   * Current version of the application.
   * @var float
   */
   public $version;
   /**
   * Required android version (number).
   * @var float
   */
   public $androidVersion;
   /**
   * Content rating.
   * @var string[]
   */
   public $contentRating;
   /**
   * Application price.
   * @var string
   */
   public $price;
   /**
   * This application is free.
   * @var boolean
   */
   public $free;
   /**
   * Screenshots of the application.
   * @var array
   */
   public $screenshots;
   /**
   * Video application.
   * @var array
   */
   public $video;
   /**
   * Application category.
   * @var string
   */
   public $genre;
   /**
   * App comments.
   * @var array
   */
   public $comments;
   /**
   * What's new in the application.
   * @var array
   */
   public $whatsnew;
   /**
   * Who offers the application.
   * @var string
   */
   public $offeredBy;
   /**
   * Information from the application developers.
   * @var array
   */
   public $developerInfo;

   public $packageName;

   public function __construct(PHPHtmlDom $dom, $packageName)
   {
      // $this->identifier = $dom->e('[data-docid]')->eq(0)->attrs->{'data-docid'};
      //  var_dump($packageName); exit;
      //   $this->identifier = $dom->e('[data-item-type="7"]')->eq(0)->attrs->{'data-item-id'};
      $this->identifier = $packageName;
      $this->url = $dom->e('[itemprop="url"]')->eq(0)->attrs->content;
      $this->summary = $dom->e('meta[name="description"]')->eq(0)->attrs->content;

      /* $this->description = $dom
      ->e('.details-section.description')
      ->find('[itemprop="description"] > div')
      ->eq(0)->text
      ; */

      $this->description = $dom->e('[jsname="sngebd"]')->eq(0)->textFormatting;
      $this->setDetailInfo($dom);
      $this->setMetaInfo($dom);
      $this->setReviewsInfo($dom);
      $this->setScreenshotsInfo($dom);
      $this->setWhatsnewInfo($dom);
   }

   private function setDetailInfo(PHPHtmlDom $dom)
   {
      $detailsInfo = $dom->e('.LXrl4c');
      $this->name = utf8_decode($dom->e('h1[itemprop="name"] > span')->eq(0)->text);
      // $this->icon = $detailsInfo->find('img[alt="Cartel"]')->eq(0)->attrs->src;
      $this->icon = $dom->e('.ujDFqe')->eq(0)->attrs->src;
      $this->genre = $detailsInfo->find('[itemprop="genre"]')->eq(0)->text;

      if (null !== $detailsInfo->find('meta[itemprop="price"]')->eq(0)) {
         $this->price = $this->normalizeFloat(
            $detailsInfo->find('meta[itemprop="price"]')->eq(0)->attrs->content
         );
      } else {
         $this->price = 0;
      }

      $this->free = $this->price == 0;

      return $this;
   }
   private function setMetaInfo(PHPHtmlDom $dom)
   {
      $metadata = $dom->e('.xyOfqd')->find(".hAyfc");
      if (is_a($metadata, 'PHPTools\PHPHtmlDom\Core\PHPHtmlDomList')) {
         $metadata->each(function ($inx, $val) use (&$histogram) {
            $block = $val->childs->find('div.BgcNfc')->eq(0)->text;


            switch ($block) {
               case 'Actualizada':
                  $this->updated = $val->childs->find('.htlgb')->eq(1)->text;
               break;
               case 'Ofrecida por':
                  $this->offeredBy = $val->childs->find('.htlgb')->eq(1)->text;
               break;
               case 'VersiÃ³n actual':
                  $this->version = [
                     'raw' => $val->childs->find('.htlgb')->eq(1)->text,
                     'value' => trim(current(explode(' ', $val->childs->find('.htlgb')->eq(1)->text))),
                  ];
               break;
               case 'Requiere Android':
                  $this->androidVersion = [
                     'raw' => $val->childs->find('.htlgb')->eq(1)->text,
                     'value' => trim(current(explode(' ', $val->childs->find('.htlgb')->eq(1)->text))),
                  ];
               break;
               case 'Descargas':
                  $this->downloads = [
                     'raw' => $val->childs->find('.htlgb')->eq(1)->text,
                     'values' => $this->normalizeFloat($val->childs->find('.htlgb')->eq(1)->text)
                  ];
               break;
               case 'ClasificaciÃ³n de contenido':
                  $this->setContentRatingInfo($val);
                  break;
               case 'Desarrollador':
                  $this->setDeveloperInfo($val);
               break;
            }
         });
      }

      return $this;
   }

   private function setAndroidVersion($metadata)
   {
      $androidVersion = $this->getElementText($metadata, '[itemprop="operatingSystems"]');
      $this->androidVersion = [
         'raw' => $androidVersion,
         'value' => trim(current(explode(' ', $androidVersion))),
      ];
      return $this;
   }

   private function setVersion($metadata)
   {
      $version = $this->getElementText($metadata, '[itemprop="softwareVersion"]');
      $this->version = [
         'raw' => $version,
         'value' => trim(current(explode(' ', $version))),
      ];
      return $this;
   }

   private function setDownloads($metadata)
   {
      $downloads = $this->getElementText($metadata, '[itemprop="numDownloads"]');
      $this->downloads = [
         'raw' => $downloads,
         'values' => array_filter(array_map(
            [$this, 'normalizeFloat'],
            explode('-',$downloads)
         ), 'floatval')
      ];
      return $this;
   }

   private function setReviewsInfo(PHPHtmlDom $dom)
   {
      if (null !== $dom->e('.EymY4b')->find('span[aria-label]')) {
         $reviewsNum = $dom->e('.EymY4b')->find('span[aria-label]')->eq(0)->text;

         $this->score = $this->normalizeFloat(
            $dom->e(".BHMmbe")->eq(0)->text
         );
         $this->reviews =  [
            'raw' => $reviewsNum,
            'value' => $this->normalizeFloat($reviewsNum),
         ];
      } else {
         $this->reviews =  [
            'raw' => 0,
            'value' => 0,
         ];
      }

      $this
         ->setHistogramInfo($dom)
         ->setCommentsInfo($dom)
      ;

      return $this;
}

   private function setScreenshotsInfo(PHPHtmlDom $dom)
   {
      $self = $this;
      $screenshotList = [];
      $video = [];
      //   $dom->e('.details-section.screenshots .thumbnails img')
      //       ->each(function ($inx, $val) use (&$screenshotList, $self) {
      //           $screenshotList[] = $self->imageUrl(
      //               str_replace('h310', 'h900', $val->attrs->src)
      //           );
      //       })
      //   ;
      //   $dom->e('.details-section.screenshots .thumbnails .details-trailer')
      //       ->each(function ($inx, $val) use (&$video, $self) {
      //           $video[] = array(
      //               'image' => $self->imageUrl(
      //                   $val->childs->find('.video-image')->eq(0)->attrs->src
      //               ),
      //               'url' => $val->childs->find('.play-action-container')->eq(0)->attrs->{'data-video-url'}
      //           );
      //       })
      //   ;

      $this->screenshots = $screenshotList;
      $this->video = $video;
      return $this;
   }

   private function setWhatsnewInfo(PHPHtmlDom $dom)
   {
      $whatsnew = [];

      if (null !== $dom->e('div[itemprop="description"]')->eq(1)) {
         $w = $dom->e('div[itemprop="description"]')->eq(1)->childs->eq(0)->text;
         // ->each(function ($inx, $val) use (&$whatsnew) {
         //     $whatsnew[] = trim(str_replace('-', '', $val->text));
         // })
         if (is_array($w)) {
            foreach ($w as $val) {
               $whatsnew[] = trim(str_replace('-', '', $val));
            }
         } else {
            $whatsnew[] = $w;
         }
      }

      $this->whatsnew = $whatsnew;
      return $this;
   }

   private function setHistogramInfo($dom)
   {
      $histogram = [];

      if (null !== $dom->e(".VEF2C")->eq(0)) {
         $ratingbarContainer = $dom->e(".VEF2C")->eq(0)->childs;
         if (is_a($ratingbarContainer, 'PHPTools\PHPHtmlDom\Core\PHPHtmlDomList')) {
            $ratingbarContainer->each(function ($inx, $val) use (&$histogram) {
               $index = $val->childs->find('.Gn2mNd')->eq(0)->text;
               $value = $val->childs->find('.L2o20d')->eq(0)->attrs->title; //->attrs->title;

               $histogram[$index] = [
                  'raw' => $value,
                  'value' => $this->normalizeFloat($value),
               ];
            });
            ksort($histogram);
         }
      } else {
         for ($i = 0; $i < 5; $i++) {
            $histogram[$i] = [
               'raw' => 0,
               'value' => 0,
            ];
         }
      }


      $this->histogram = $histogram;
      return $this;
   }
   private function setCommentsInfo($dom)
   {
      //   $comments = [];
      //   $featuredReview = $dom->e("script");
      //   var_dump($featuredReview); exit;
      //
      //   if (is_a($featuredReview, 'PHPTools\PHPHtmlDom\Core\PHPHtmlDomList')) {
      //       $featuredReview->each(function ($inx, $val) use (&$comments) {
      //           $comments[] = $val->text;
      //       });
      //   }
      $this->comments = [];
      return $this;
   }
   private function setContentRatingInfo($metadata)
   {
      $contentRating = [];
      $contentRatingList = $metadata->childs->find('span.htlgb > div');

      if (is_a($contentRatingList, 'PHPTools\PHPHtmlDom\Core\PHPHtmlDomList')) {
         $contentRatingList->each(function ($inx, $val) use (&$contentRating) {
            $text = $val->textFormatting;

            if ($text) {
               $contentRating[] = $val->textFormatting;
            }
         });
      }

      $this->contentRating = $contentRating;
      return $this;
   }
   private function setDeveloperInfo($metadata)
   {
      $developerInfo = [];
      $developerInfoList = $metadata->childs->find('span.htlgb > div')->eq(0)->childs;

      if (is_a($developerInfoList, 'PHPTools\PHPHtmlDom\Core\PHPHtmlDomList')) {
         $developerInfoList->each(function ($inx, $val) use (&$developerInfo) {
            if (isset($val->childs->find('a')->eq(0)->attrs->href)) {
               $url = $val->childs->find('a')->eq(0)->attrs->href;
            } else {
               $url = "";
            }

            if (isset($val->childs->find('a')->eq(1)->attrs->href)) {
               $email = str_replace("mailto:", "", $val->childs->find('a')->eq(1)->attrs->href);
            } else {
               $email = "";
            }

            $developerInfo['email'] = $email;
            $developerInfo['url'] = $url;
         });
      }

      $this->developerInfo = $developerInfo;
      return $this;
   }

   private function imageUrl($url)
   {
      return sprintf(
         "%s:%s",
         parse_url($this->url, PHP_URL_SCHEME),
         $url
      );
   }

   public function getElementText($dom, $selector, $inx = 0)
   {
      $value = '';
      $elements = $dom->find($selector);
      if (is_a($elements, 'PHPTools\PHPHtmlDom\Core\PHPHtmlDomList') && $elements->count()) {
         $value = $elements->eq($inx)->text;
      }
      return $value;
   }
   public function normalizeFloat($val)
   {
      $val = str_replace(',', '.', str_replace('.', '', $val));
      return is_string($val) ? floatval($val) : $val;
   }
}
