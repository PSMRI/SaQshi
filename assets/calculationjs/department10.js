
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
            denominator = parseFloat(document.getElementById('input2').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator)*1000); // Example calculation
                document.getElementById('result1').value = result.toFixed(2);
            } else {
                document.getElementById('result1').value = 'Invalid input';
            }
            break;        
        case 2:
            numerator = parseFloat(document.getElementById('input3').value);
            //denominator = parseFloat(document.getElementById('input4').value);
            if (!isNaN(numerator)) {
                result = ((numerator) ); // Example calculation
                document.getElementById('result2').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result2').value = 'Invalid input';
            }
            break;
        case 3:
            numerator = parseFloat(document.getElementById('input5').value);
            //denominator = parseFloat(document.getElementById('input6').value);
            if (!isNaN(numerator) ) {
                result = ((numerator) ); // Example calculation
                document.getElementById('result3').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result3').value = 'Invalid input';
            }
            break;
        case 4:
            numerator = parseFloat(document.getElementById('input7').value);
           // denominator = parseFloat(document.getElementById('input8').value);
            if (!isNaN(numerator) ) {
                result = ((numerator) );// Example calculation
                document.getElementById('result4').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result4').value = 'Invalid input';
            }
            break;
        case 5:
            numerator = parseFloat(document.getElementById('input9').value);
            denominator = parseFloat(document.getElementById('input10').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result5').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result5').value = 'Invalid input';
            }
            break;
        case 6:
            numerator = parseFloat(document.getElementById('input11').value);
            denominator = parseFloat(document.getElementById('input12').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result6').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result6').value = 'Invalid input';
            }
            break;
        case 7:
            numerator = parseFloat(document.getElementById('input13').value);
            denominator = parseFloat(document.getElementById('input14').value);
            if (!isNaN(numerator) ) {
                result = (numerator/denominator); // Example calculation
                document.getElementById('result7').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result7').value = 'Invalid input';
            }
            break;
        case 8:
            numerator = parseFloat(document.getElementById('input15').value);
            denominator = parseFloat(document.getElementById('input26').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator)); // Example calculation
                document.getElementById('result8').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result8').value = 'Invalid input';
            }
            break;
        case 9:
            numerator = parseFloat(document.getElementById('input17').value);
            denominator = parseFloat(document.getElementById('input18').value);
            if (!isNaN(numerator)) {
                result = (numerator/denominator ); // Example calculation
                document.getElementById('result9').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result9').value = 'Invalid input';
            }
            break;
        case 10:
            numerator = parseFloat(document.getElementById('input19').value);
            denominator = parseFloat(document.getElementById('input20').value);
            if (!isNaN(numerator)) {
                result = (numerator/denominator ); // Example calculation
                document.getElementById('result10').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result10').value = 'Invalid input';
            }
            break;
        case 11:
            numerator = parseFloat(document.getElementById('input21').value);
            denominator = parseFloat(document.getElementById('input22').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator*100) / (denominator)); // Example calculation
                document.getElementById('result11').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result11').value = 'Invalid input';
            }
            break;
        case 12:
            numerator = parseFloat(document.getElementById('input23').value);
           // denominator = parseFloat(document.getElementById('input24').value);
            if (!isNaN(numerator)) {
                result = (numerator); // Example calculation
                document.getElementById('result12').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result12').value = 'Invalid input';
            }
            break;
        case 13:
            numerator = parseFloat(document.getElementById('input25').value);
            denominator = parseFloat(document.getElementById('input26').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator/ denominator)*1000); // Example calculation
                document.getElementById('result13').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result13').value = 'Invalid input';
            }
            break;
        case 14:
            numerator = parseFloat(document.getElementById('input27').value);
            denominator = parseFloat(document.getElementById('input28').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator/denominator)*1000); // Example calculation
                document.getElementById('result14').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result14').value = 'Invalid input';
            }
            break;
            case 15:
                numerator = parseFloat(document.getElementById('input29').value);
                denominator = parseFloat(document.getElementById('input30').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator * 1000) / (denominator)); // Example calculation
                    document.getElementById('result15').value = result.toFixed(2) + '%';
                } else {
                    document.getElementById('result15').value = 'Invalid input';
                }
                break;
                case 16:
                numerator = parseFloat(document.getElementById('input31').value);
                denominator = parseFloat(document.getElementById('input32').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator * 1000) / (denominator)); // Example calculation
                    document.getElementById('result16').value = result.toFixed(2) + '%';
                } else {
                    document.getElementById('result16').value = 'Invalid input';
                }
                break;
                case 17:
                numerator = parseFloat(document.getElementById('input33').value);
                denominator = parseFloat(document.getElementById('input34').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator * 1000) / (denominator)); // Example calculation
                    document.getElementById('result17').value = result.toFixed(2) + '%';
                } else {
                    document.getElementById('result17').value = 'Invalid input';
                }
                break;
                case 18:
                numerator = parseFloat(document.getElementById('input35').value);
                //denominator = parseFloat(document.getElementById('input36').value);
                if (!isNaN(numerator) ) {
                    result = (numerator ); // Example calculation
                    document.getElementById('result18').value = result.toFixed(2) + '%';
                } else {
                    document.getElementById('result18').value = 'Invalid input';
                }
                break;
        // Add additional cases for more rows if needed
        // Example:
        // case 4:
        //     numerator = parseFloat(document.getElementById('input7').value);
        //     denominator = parseFloat(document.getElementById('input8').value);
        //     // Calculation logic for row 4
        //     break;

        default:
            break; // Default case for invalid row numbers
    }
}
