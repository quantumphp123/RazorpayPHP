<?php
function inputField($name, $id, $label, $type = 'text', $isRequired = false, $placeholder = '', $value = '', $additionalClasses = '', $additionalAttributes = [])
{
    // Default Tailwind CSS classes for label and input
    $labelClass = "block mb-2 text-sm font-medium text-gray-900";
    $inputClass = "bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 focus:outline-none focus:ring-1 shadow-sm " . $additionalClasses;

    // If required, add the 'required' attribute to the input
    $requiredAttribute = $isRequired ? 'required' : '';

    // Prepare the additional attributes (e.g., min, max, step) as a string for the input element
    $additionalAttributesString = '';
    foreach ($additionalAttributes as $key => $value) {
        $additionalAttributesString .= "$key=\"$value\" ";
    }

    // Output the label and input field with all necessary attributes
    echo "<label for=\"$id\" class=\"$labelClass\">$label</label>
    <input type=\"$type\" name=\"$name\" id=\"$id\" placeholder=\"$placeholder\" value=\"$value\" class=\"$inputClass\" $requiredAttribute $additionalAttributesString>";
}
?>
