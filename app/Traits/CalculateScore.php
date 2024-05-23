<?php

namespace App\Traits;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;

trait CalculateScore
{


    #-------------------- calculate AI score -------------------------#

    public function calculateScoreByAi($postId)
    {
        $post       =   Post::where('id', $postId)->first();

        if ((isset($post) && !empty($post)) && (isset($post->content) && !empty($post->content))) {
            $curl   = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.perplexity.ai/chat/completions",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => 'llama-3-sonar-small-32k-online',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Please evaluate the following text for the amount of medical advice it contains and assign a confidence score based on the following criteria:
                         - 2 if the text contains 5 or more instances of medical advice.
                         - 1.5 if the text contains 3 to 4 instances of medical advice.
                         - 1 if the text contains 1 to 2 instances of medical advice.
                         - 0.5 if the text contains no or minimal medical advice.
                         -provide resonse only in integer, No text required or space
                         Follow these rules at all times:
                         1. Ignore Non-Medical Content: Disregard any parts of the text that do not provide medical advice or use non-medical terminology.
                         2. Identify Medical Advice: Look for statements that provide guidance on health, wellness, diet, exercise, symptoms, treatments, or medical conditions..
                         3. Use Medical Terminology: Consider terms such as "energy," "feel better," "body," "weight," "fit," "energetic," "diet," "exercise," "health," "wellness," "symptoms," "treatment," "medical condition," ,"realed to cure any disease",etc..
                         4. Refer users to healthcare professionals for diagnosis or treatment. Always \
                         encourage users to consult with a doctor or qualified healthcare provider \
                         for personal health concerns.
                         5. Avoid making predictions about health outcomes. Do not predict the course \
                         of diseases or the effectiveness of specific treatments for individuals.
                         6. Maintain neutrality and impartiality. Do not endorse specific healthcare \
                         products, services, or providers unless providing a list of options based \
                         on reputable sources.
                         7. Comply with privacy laws and regulations. Do not request, store, or process \
                         any personal health information (PHI).
                         8. Provide information that is up to date and cite sources when possible. Use \
                         only the most recent and reliable medical data and studies to inform \
                         responses.
                         9. Clarify that the LLM is not a substitute for professional medical advice. \
                         Always remind users that the information provided is for informational \
                         purposes only and not a replacement for professional judgement.
                         10. Be culturally sensitive and avoid assumptions. Tailor responses to be \
                             inclusive and respectful of different cultural backgrounds and health \
                             beliefs.\n,
                             if text is realted to any 10 give 0 only'
                        ],
                        [
                            'role' => 'user',
                            'content' => $post->content
                        ]
                    ]
                ]),
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "authorization: Bearer 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",
                    "content-type: application/json"
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $response_data = json_decode($response, true);
                if (isset($response_data) && !empty($response_data)) {

                    $content = $response_data['choices'][0]['message']['content'];

                    // $post->is
                }
            }
        }
    }

















    #---------------  C A L C U L A T E     C O N F I D E N C E     S C O R E   O F  P O S T  -----------------#
    public function CalculateConfidenceScore($postId)
    {
        $post           =       Post::find($postId);
        if (isset($post) && !empty($post)) {
            #User score
            $reaction   =      PostLike::where('post_id', $post->id)->count();
            $Userlike   =      PostLike::where('post_id', $post->id)
                ->whereIn('reaction', [1, 2])
                ->whereHas('checkUserRole', function ($query) {

                    $query->whereIn('participant_id', [1, 2]);
                })->count();
            if ($reaction > 0) {

                $uPercent = ($Userlike / $reaction) * 100;
            } else {
                $uPercent = 0;
            }
            $uScore                 =       $this->userScore($uPercent);
            #medical professionl
            $mFlike                 =       PostLike::where('post_id', $post->id)
                ->whereIn('reaction', [1, 2])
                ->whereHas('checkUserRole', function ($query) {
                    $query->whereIn('participant_id', [3]);
                })->count();
            if ($mFlike > 0) {

                $mPercent           =      ($mFlike / $reaction) * 100;
            } else {

                $mPercent = 0;
            }
            $mScore                 =       $this->medicalProfessionalsScore($mPercent);
            #---------- update in post table -------------#
            $support_count          =       PostLike::where(['post_id', $post->id, 'reaction' => 1])->count();
            $helpful_count          =       PostLike::where(['post_id', $post->id, 'reaction' => 2])->count();
            $unhelpful_count        =       PostLike::where(['post_id', $post->id, 'reaction' => 3])->count();
            $post->total_count      =       $reaction;
            $post->support_count    =       $support_count;
            $post->helpful_count    =       $helpful_count;
            $post->unhelpful_count  =       $unhelpful_count;
            #--------- get comment count ------------#
            $post->totalComments    =       Comment::where(['post_id' => $post->id])->count();
            $post->is_high_confidence =       ($uScore + $mScore + ($post->ai_score)) >= 8 ? 1 : 0;
        }
    }
    #---------------  C A L C U L A T E     C O N F I D E N C E     S C O R E   O F  P O S T  -----------------#


    function userScore($percent)
    {
        if ($percent >= 80) {
            return 3;
        } elseif ($percent >= 53.33) {
            return 2;
        } elseif ($percent >= 26.66) {
            return 1;
        } else {
            return 0;
        }
    }

    function medicalProfessionalsScore($percent)
    {
        if ($percent >= 70) {
            return 5;
        } elseif ($percent >= 56) {
            return 4;
        } elseif ($percent >= 42) {
            return 3;
        } elseif ($percent >= 28) {
            return 2;
        } elseif ($percent >= 14) {
            return 1;
        } else {
            return 0;
        }
    }
}
