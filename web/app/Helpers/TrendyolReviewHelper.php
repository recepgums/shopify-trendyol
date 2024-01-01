<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class TrendyolReviewHelper
{
    /**
     * @param $apiUrl
     * @param $shopifyProductSlug
     * @param $count
     * @param bool $onlyImages
     * @return string
     */
    public function getCsvFileFromTrendyolUrl($apiUrl, $shopifyProductSlug, $count, bool $onlyImages = false)
    {
        $commentCount = 0;
        $csvContent = 'Review ID,Product ID,Product Model,Customer_id,Customer email,Author,Text,Rating,Status,Date Added (YYYY-MM-DD),Date Modified (YYYY-MM-DD),product_handle*,rating*,author*,content,author_country,author_email,commented_at*,reply,reply_at,verify_purchase,feature,photo_url_1,photo_url_2,photo_url_3,photo_url_4,photo_url_5,video_url';

        $page = 1;
        while ($commentCount < $count) {
            $paginatedUrl = "$apiUrl?page=$page";
            $response = Http::withHeaders(['Accept' => 'application/json; charset=utf-8'])->get($paginatedUrl);

            if ($response->status() == 200) {
                $data = $response->json();
                $hydrateScript = html_entity_decode($data['result']['hydrateScript'], ENT_QUOTES, 'UTF-8');

                $crawler = new Crawler($hydrateScript);

                $scripts = $crawler->filter('script[type="application/javascript"]');

                $jsData = [];

                $scripts->each(function ($script) use (&$jsData) {
                    $match = preg_match('/window\.__REVIEW_APP_INITIAL_STATE__ = ({.*?});/', $script->text(), $matches);
                    if ($match) {
                        $jsData = json_decode($matches[1], true);
                    }
                });

                $ratingAndReview = data_get($jsData, 'ratingAndReviewResponse.ratingAndReview.productReviews.content', []);

                foreach ($ratingAndReview as $comment) {
                    if ($onlyImages && empty($comment['mediaFiles'])) {
                        continue;
                    }

                    $productHandle = $shopifyProductSlug;
                    $rating = data_get($comment, 'rate');
                    $author = data_get($comment, 'userFullName');
                    $content = html_entity_decode(data_get($comment, 'comment'), ENT_QUOTES, 'UTF-8');
                    $authorCountry = "TR";
                    $authorEmail = "your_author_email_here";
                    $commentedAt = data_get($comment, 'commentDateISOtype');
                    $reply = "";
                    $replyAt = "";
                    $verifyPurchase = "your_verify_purchase_here";
                    $feature = "your_feature_here";
                    $photoUrl1 = data_get($comment, 'mediaFiles.0.url', '');
                    $photoUrl2 = "";
                    $photoUrl3 = "";
                    $photoUrl4 = "";
                    $photoUrl5 = "";
                    $videoUrl = "";

                    // Write comment data to the CSV file
                    $csvData = [
                        $productHandle,
                        $rating,
                        $author,
                        $content,
                        $authorCountry,
                        $authorEmail,
                        $commentedAt,
                        $reply,
                        $replyAt,
                        $verifyPurchase,
                        $feature,
                        $photoUrl1,
                        $photoUrl2,
                        $photoUrl3,
                        $photoUrl4,
                        $photoUrl5,
                        $videoUrl
                    ];

                    $csvContent .= implode(',', $csvData) . PHP_EOL;
                    $commentCount++;

                    if ($commentCount >= $count) {
                        break;
                    }
                }
            } else {
                echo "Failed to fetch data. Status code: " . $response->status() . PHP_EOL;
                break;
            }

            $page++;
        }

        return $csvContent;
    }
    /**
     * @param $trendyolProductUrl
     * @return string
     */
    public function convertTrendyolToCommentUrl($trendyolProductUrl)
    {
        $commentUrl = str_replace("https://www.trendyol.com/", "https://public-mdc.trendyol.com/discovery-web-socialgw-service/reviews/", $trendyolProductUrl);
        $commentUrl = explode('?', $commentUrl)[0];
        $commentUrl .= "/yorumlar";

        return $commentUrl;
    }
}
