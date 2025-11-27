
function calculateResult(rowIndex) {
    var numerator, denominator, result;
    // Get the current date to determine the number of days in the current month
    var currentDate = new Date();
    var currentMonth = currentDate.getMonth();
    var currentYear = currentDate.getFullYear();
    var dayOfMonth = new Date(currentYear, currentMonth + 1, 0).getDate(); // Get the number of days in the current month
}
function calculateResult(row) {
    var numerator, denominator, result;
    // Get the current date to determine the number of days in the current month
    var currentDate = new Date();
    var currentMonth = currentDate.getMonth();
    var currentYear = currentDate.getFullYear();
    var dayOfMonth = new Date(currentYear, currentMonth + 1, 0).getDate(); // Get the number of days in the current month
    // Handle different rows using a switch statement
    switch (row) {
        case 1:
            numerator = parseFloat(document.getElementById('input1').value);
           // denominator = parseFloat(document.getElementById('input2').value);
            if (!isNaN(numerator)  && numerator!== 0) {
                result = (numerator ); // Example calculation
                document.getElementById('result1').value = result.toFixed(2);
            } else {
                document.getElementById('result1').value = 'Invalid input';
            }
            break;

        case 2:
            numerator = parseFloat(document.getElementById('input3').value);
           // denominator = parseFloat(document.getElementById('input4').value);
            if (!isNaN(numerator)  && numerator!== 0) {
                result = (numerator ); // Example calculation
                document.getElementById('result2').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result2').value = 'Invalid input';
            }
            break;

        case 3:
            numerator = parseFloat(document.getElementById('input5').value);
           // denominator = parseFloat(document.getElementById('input6').value);
            if (!isNaN(numerator)  && numerator !== 0) {
                result = ((numerator)); // Example calculation
                document.getElementById('result3').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result3').value = 'Invalid input';
            }
            break;
        case 4:
            numerator = parseFloat(document.getElementById('input7').value);
           // denominator = parseFloat(document.getElementById('input8').value);
            if (!isNaN(numerator)  && numerator !== 0) {
                result = (numerator); // Example calculation
                document.getElementById('result4').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result4').value = 'Invalid input';
            }
            break;
        case 5:
            numerator = parseFloat(document.getElementById('input9').value);
            denominator = parseFloat(document.getElementById('input10').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = (numerator/denominator); // Example calculation
                document.getElementById('result5').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result5').value = 'Invalid input';
            }
            break;
        default:
            break; // Default case for invalid row numbers
    }
}
