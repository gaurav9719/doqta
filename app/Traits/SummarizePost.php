<?php

namespace App\Traits;

use App\Models\Post;
use Exception;
use GeminiAPI\Resources\Parts\TextPart;
use GeminiAPI\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

trait SummarizePost
{
    #--------------  S U M M A R I Z E      T H E       P O S T  ---------------------#
    public function summerize($postid)
    {

        try {

            $postData                       =           Post::where(['id' => $postid])->first();
            //dd($postData);

            if ($postData['media_type'] == 0 || $postData['media_type'] == 2 || $postData['media_type'] == 3 || $postData['media_type'] == 2) {                                           // text
                if (isset($postData['content']) && !empty($postData['content'])) {

                    $prompt                 =           "summarize this text in 150 words: " . $postData['content'];

                    $postContent            =           $this->postSummaryInstruction($prompt);

                    $summary                =           Gemini::generateText($postContent);
                }
            } elseif ($postData['media_type'] == 1) {                   // image

                $prompt                      =   "";
                if (isset($postData['content']) && !empty($postData['content'])) {

                    $prompt                 =           "summarize this text and picture and combine into maximum 150 words text: " . $postData['content'];
                } else {

                    $prompt                 =           'summarize this image in maximum 150 characters';
                }
                if (isset($postData['media_url']) && !empty($postData['media_url'])) {

                    $imageUrl               =           asset('storage/' . $postData['media_url']);
                    // dd($imageUrl);
                    $extension              =           File::extension($imageUrl);
                    // dd($extension);

                    $postContent            =           $this->postSummaryInstruction($prompt);

                    $summary                =           Gemini::generateTextUsingImage('image/' . $extension, base64_encode(file_get_contents($imageUrl)), $postContent);
                }
            }
            $postData->summarize            =           $summary;
            $postData->save();
            Log::info("working summarize");
            return 200;
        } catch (Exception $e) {

            return 400;
        }
    }

    public function postSummaryInstruction($content)
    {

        // $systemInstruction = 'Generate PHP code to display "Hello World"';

        $guidelines = [
            [
                "text" => "System: You are now a specialized AI assistant for Doqta, focused on creating clear, concise, and culturally sensitive summaries of health forum posts for the Black community. Follow these guidelines to ensure your summaries are informative and accessible:"
            ],
            [
                "text" => "Capture the Essence: Identify and highlight the main health topic or concern. Distill key points and questions from the user."
            ],
            [
                "text" => "Simplify Language: Use plain, everyday language that's easily understood by a broad audience. Replace medical jargon with simpler terms when possible, without losing accuracy.If a medical term is crucial, provide a brief, clear explanation in parentheses"
            ],
            [
                "text" => "Maintain Brevity: Keep summaries concise, ideally no more than 3-4 sentences.Focus on the most relevant and impactful information from the original post"
            ],
            [
                "text" => "Preserve Cultural Context: Be mindful of and retain any culturally specific references or concerns, if present. Use culturally appropriate language and examples when clarifying points"
            ],
            [
                "text" => "Highlight Key Elements: Clearly state the health condition, symptoms, or situation being discussed.Note any specific questions or requests for advice made by the original poster.Mention any unique experiences or perspectives shared that might be valuable to others."
            ],
            [
                "text" => "Maintain Neutrality: Present information objectively, without adding personal opinions or medical advice.If the original post contains potentially harmful or inaccurate information, flag it neutrally (e.g., 'Note: This post contains health claims that may require professional verification')"

            ],
            [
                "text" => "Omit any personally identifiable information from the summary.Use general terms instead of specific names or locations (e.g., 'the poster's doctor' instead of 'Dr. Smith')"
            ],
            [
                "text" => "Capture Emotional Context: Briefly convey the emotional tone of the post (e.g.,'The user expresses concern about... or The poster is seeking support for...'). This helps other users understand the poster's state of mind and respond appropriately"
            ],
            [
                "text" => "Structure for Clarity: Use a consistent format for all summaries to aid quick comprehension.Consider a structure like: [Main Topic] - [Key Points/Questions] - [User's Situation/Experience]"
            ],
            [
                "text" => "Highlight Actionable Elements: If the post includes any calls to action or requests for specific types of support, make these clear in the summary"
            ],
            [
                "text" => "Focus only on health-related aspects of the post, even if other topics are mentioned.If a post is not primarily health-related, state this clearly in the summary."
            ],
            [
                "text" => "Employ language that is respectful and inclusive of diverse experiences within the Black community.Avoid assumptions or generalizations based on race or ethnicity."
            ],
            [
                "text" => "Flag Urgent Concerns: If a post indicates a potentially urgent health situation, include a note at the beginning of the summary (e.g., 'Urgent: This post describes symptoms that may require immediate medical attention')."
            ],
            [
                "text" => "Encourage Engagement: End the summary with a brief statement that encourages other users to read the full post if they can relate or have insights to share."
            ],
            [
                "text" => "Maintain Health Focus: Ensure all summaries pertain strictly to medical and health-related topics.If a post contains non-health-related content, focus the summary only on the health aspects",
            ],
            [
                "text" => "Sample Summary Structure:[Health Topic]: User shares experience with [specific condition/symptom]. Key points: [1-2 main ideas]. Seeking: [advice/support/information] on [specific aspect]. Note: [Any important flags or cultural context]."

            ],
            ["text" => "Your goal is to create summaries that allow users to quickly understand the content of health forum posts, decide if they're relevant to their own experiences, and determine whether they want to read the full post or engage with the discussion. Always prioritize clarity, relevance, and cultural sensitivity in your summaries"]
        ];
        $prompt_template = (

            "{system_instruction} content: {question}."
        );
        // Compile the prompt using the provided parameters
        $compiled_prompt = str_replace(
            ['{system_instruction}', '{question}'],
            [json_encode($guidelines), $content,],
            $prompt_template
        );
        return $compiled_prompt;

    }

    #-------------------------  C O M M E N T       T H R E A D     S U M M A R Y -------------------------#
    public function commentThreadSummary(){


    }
    #-------------------------  C O M M E N T       T H R E A D     S U M M A R Y -------------------------#
}
