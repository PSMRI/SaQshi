
function calculateResult(row) {
  var numerator, denominator, result;

    // Get days in selected month
    let selectedDateParts = selectedMonth.split('-'); // Format: "YYYY-MM"
    let selectedYear = parseInt(selectedDateParts[0]);
    let selectedMonthIndex = parseInt(selectedDateParts[1]) - 1; // JavaScript Date months are 0-indexed
    let dayOfMonth = new Date(selectedYear, selectedMonthIndex + 1, 0).getDate();
    switch (row) {
        case 1:
            numerator = parseFloat(document.getElementById('input1').value);
            denominator = parseFloat(document.getElementById('input2').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = (numerator/denominator)*100; // Example calculation
                document.getElementById('result1').value = result.toFixed(2);
            } else {
                document.getElementById('result1').value = 'Invalid input';
            }
            break;

        case 2:
            numerator = parseFloat(document.getElementById('input3').value);
            denominator = parseFloat(document.getElementById('input4').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator*1000) / (denominator)); // Example calculation
                document.getElementById('result2').value = result.toFixed(2) ;
            } else {
                document.getElementById('result2').value = 'Invalid input';
            }
            break;

        case 3:
            numerator = parseFloat(document.getElementById('input5').value);
            denominator = parseFloat(document.getElementById('input6').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result =((numerator*1000) / denominator); // Example calculation
                document.getElementById('result3').value = result.toFixed(2) ;
            } else {
                document.getElementById('result3').value = 'Invalid input';
            }
            break;
        case 4:
            numerator = parseFloat(document.getElementById('input7').value);
            denominator = parseFloat(document.getElementById('input8').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator*1000) / denominator); // Example calculation
                document.getElementById('result4').value = result.toFixed(2) ;
            } else {
                document.getElementById('result4').value = 'Invalid input';
            }
            break;
        case 5:
            numerator = parseFloat(document.getElementById('input9').value);
            denominator = parseFloat(document.getElementById('input10').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator * 1000) / (denominator)); // Example calculation
                document.getElementById('result5').value = result.toFixed(2) ;
            } else {
                document.getElementById('result5').value = 'Invalid input';
            }
            break;
        case 6:
            numerator = parseFloat(document.getElementById('input11').value);
           // denominator = parseFloat(document.getElementById('input12').value);
            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result6').value = result.toFixed(2) ;
            } else {
                document.getElementById('result6').value = 'Invalid input';
            }
            break;
        case 7:
            numerator = parseFloat(document.getElementById('input13').value);
            denominator = parseFloat(document.getElementById('input14').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result7').value = result.toFixed(2) ;
            } else {
                document.getElementById('result7').value = 'Invalid input';
            }
            break;
        case 8:
            numerator = parseFloat(document.getElementById('input15').value);
            denominator = parseFloat(document.getElementById('input16').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result8').value = result.toFixed(2) ;
            } else {
                document.getElementById('result8').value = 'Invalid input';
            }
            break;
        case 9:
            numerator = parseFloat(document.getElementById('input17').value);
            denominator = parseFloat(document.getElementById('input18').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result9').value = result.toFixed(2) ;
            } else {
                document.getElementById('result9').value = 'Invalid input';
            }
            break;
        case 10:
            numerator = parseFloat(document.getElementById('input19').value);
            denominator = parseFloat(document.getElementById('input20').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator)); // Example calculation
                document.getElementById('result10').value = result.toFixed(2) ;
            } else {
                document.getElementById('result10').value = 'Invalid input';
            }
            break;
        case 11:
            numerator = parseFloat(document.getElementById('input21').value);
            denominator = parseFloat(document.getElementById('input22').value);
            if  (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)) // Example calculation
                document.getElementById('result11').value = result.toFixed(2) ;
            } else {
                document.getElementById('result11').value = 'Invalid input';
            }
            break;
        case 12:
            numerator = parseFloat(document.getElementById('input23').value);
            denominator = parseFloat(document.getElementById('input24').value);
            if  (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result12').value = result.toFixed(2) ;
            } else {
                document.getElementById('result12').value = 'Invalid input';
            }
            break;
        case 13:
            numerator = parseFloat(document.getElementById('input25').value);
            denominator = parseFloat(document.getElementById('input26').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result13').value = result.toFixed(2) ;
            } else {
                document.getElementById('result13').value = 'Invalid input';
            }
            break;
        case 14:
            numerator = parseFloat(document.getElementById('input27').value);
            denominator = parseFloat(document.getElementById('input28').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result14').value = result.toFixed(2) ;
            } else {
                document.getElementById('result14').value = 'Invalid input';
            }
            break;
        case 15:
            numerator = parseFloat(document.getElementById('input29').value);
            denominator = parseFloat(document.getElementById('input30').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result15').value = result.toFixed(2) ;
            } else {
                document.getElementById('result15').value = 'Invalid input';
            }
            break;
        case 16:
            numerator = parseFloat(document.getElementById('input31').value);
            denominator = parseFloat(document.getElementById('input32').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator) / (denominator)); // Example calculation
                document.getElementById('result16').value = result.toFixed(2) ;
            } else {
                document.getElementById('result16').value = 'Invalid input';
            }
            break;
            case 17:
                numerator = parseFloat(document.getElementById('input33').value);
                denominator = parseFloat(document.getElementById('input34').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator) / (denominator)); // Example calculation
                    document.getElementById('result17').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result17').value = 'Invalid input';
                }
                break;
                case 18:
                numerator = parseFloat(document.getElementById('input35').value);
                denominator = parseFloat(document.getElementById('input36').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator) / (denominator)); // Example calculation
                    document.getElementById('result18').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result18').value = 'Invalid input';
                }
                break;
                case 19:
                numerator = parseFloat(document.getElementById('input37').value);
                denominator = parseFloat(document.getElementById('input38').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator) / (denominator)); // Example calculation
                    document.getElementById('result19').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result19').value = 'Invalid input';
                }
                break;
                case 20:
                numerator = parseFloat(document.getElementById('input39').value);
                denominator = parseFloat(document.getElementById('input40').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator) / (denominator))*100; // Example calculation
                    document.getElementById('result20').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result20').value = 'Invalid input';
                }
                break;
                case 21:
                numerator = parseFloat(document.getElementById('input41').value);
                denominator = parseFloat(document.getElementById('input42').value);
                if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                    result = ((numerator) / (denominator))*100; // Example calculation
                    document.getElementById('result21').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result21').value = 'Invalid input';
                }
                break;
                case 22:
                numerator = parseFloat(document.getElementById('input43').value);
               // denominator = parseFloat(document.getElementById('input44').value);
                if (!isNaN(numerator)) {
                    result = ((numerator)); // Example calculation
                    document.getElementById('result22').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result22').value = 'Invalid input';
                }
                break;
                case 23:
                numerator = parseFloat(document.getElementById('input45').value);
                //denominator = parseFloat(document.getElementById('input46').value);
                if (!isNaN(numerator) ) {
                    result = ((numerator) ); // Example calculation
                    document.getElementById('result23').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result23').value = 'Invalid input';
                }
                break;
                case 24:
                numerator = parseFloat(document.getElementById('input47').value);
                //denominator = parseFloat(document.getElementById('input48').value);
                if (!isNaN(numerator) ) {
                    result = ((numerator) ); // Example calculation
                    document.getElementById('result24').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result24').value = 'Invalid input';
                }
                break;
                case 25:
                numerator = parseFloat(document.getElementById('input49').value);
                //denominator = parseFloat(document.getElementById('input50').value);
                if (!isNaN(numerator)) {
                    result = ((numerator)); // Example calculation
                    document.getElementById('result25').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result25').value = 'Invalid input';
                }
                break;
                case 26:
                numerator = parseFloat(document.getElementById('input51').value);
                //denominator = parseFloat(document.getElementById('input52').value);
                if (!isNaN(numerator)) {
                    result = ((numerator)); // Example calculation
                    document.getElementById('result26').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result26').value = 'Invalid input';
                }
                break;
                case 27:
                numerator = parseFloat(document.getElementById('input53').value);
                //denominator = parseFloat(document.getElementById('input54').value);
                if (!isNaN(numerator)) {
                    result = ((numerator)); // Example calculation
                    document.getElementById('result27').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result27').value = 'Invalid input';
                }
                break;
                case 28:
                numerator = parseFloat(document.getElementById('input55').value);
                //denominator = parseFloat(document.getElementById('input56').value);
                if (!isNaN(numerator)) {
                    result = ((numerator)); // Example calculation
                    document.getElementById('result28').value = result.toFixed(2) ;
                } else {
                    document.getElementById('result28').value = 'Invalid input';
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
