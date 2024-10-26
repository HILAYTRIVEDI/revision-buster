// Function to toggle the visibility of the multi-select container
function toggleMultiSelect()
{
    var multiselectContainer = document.getElementById("multiselect_container");
    var enableMultiselect = document.getElementById("enable_multiselect");

    if (enableMultiselect.checked)
    {
        multiselectContainer.style.display = "block";
    } else
    {
        multiselectContainer.style.display = "none";
    }
}

// Attach the toggle function to the checkbox
document.addEventListener("DOMContentLoaded", function ()
{
    var enableMultiselect = document.getElementById("enable_multiselect");
    if (enableMultiselect)
    {
        enableMultiselect.addEventListener("change", toggleMultiSelect);
    }
});

// On click of enable_multiselect checkbox, call toggleMultiSelect function
document.getElementById("enable_multiselect").addEventListener("click", toggleMultiSelect);