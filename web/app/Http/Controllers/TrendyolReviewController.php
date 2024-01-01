<?php

namespace App\Http\Controllers;

use App\Helpers\TrendyolReviewHelper;
use Illuminate\Http\Request;

class TrendyolReviewController extends Controller
{
    public function download(Request $request)
    {
        try {
            $trendyol = new TrendyolReviewHelper();
            $commentApiUrl = $trendyol->convertTrendyolToCommentUrl($request->get('productLink'));
            $csvContent = $trendyol->getCsvFileFromTrendyolUrl(
                $commentApiUrl,
                $request->get('targetProduct'),
                $request->get('reviewCountLimit'),
                true
            );

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="comment_chatgpt.csv"',
            ];

            return response()->make($csvContent, 200, $headers);
        } catch (\Exception $e) {
            // Handle exceptions if needed
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
}
