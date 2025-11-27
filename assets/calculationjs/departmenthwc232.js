function calculateResult(row) {
    var numerator, denominator, result;
    switch (row) {
        case 1:
            numerator = parseFloat(document.getElementById('input1').value);
            if (!isNaN(numerator)) {
                result = (numerator); // Example calculation
                document.getElementById('result1').value = result.toFixed(2);
            } else {
                document.getElementById('result1').value = 'Invalid input';
            }
            break;

        case 2:
            numerator = parseFloat(document.getElementById('input3').value);

            if (!isNaN(numerator)) {
                result = numerator; // Example calculation
                document.getElementById('result2').value = result.toFixed(2);
            } else {
                document.getElementById('result2').value = 'Invalid input';
            }
            break;

        case 3:
            numerator = parseFloat(document.getElementById('input5').value);

            if (!isNaN(numerator)) {
                result = numerator; // Example calculation
                document.getElementById('result3').value = result.toFixed(2);
            } else {
                document.getElementById('result3').value = 'Invalid input';
            }
            break;
        case 4:
            numerator = parseFloat(document.getElementById('input7').value);
            if (!isNaN(numerator)) {
                result = numerator; // Example calculation
                document.getElementById('result4').value = result.toFixed(2);
            } else {
                document.getElementById('result4').value = 'Invalid input';
            }
            break;
        case 5:
            numerator = parseFloat(document.getElementById('input9').value);

            if (!isNaN(numerator)) {
                result = (numerator);
                document.getElementById('result5').value = result.toFixed(2);
            } else {
                document.getElementById('result5').value = 'Invalid input';
            }
            break;
        case 6:
            numerator = parseFloat(document.getElementById('input11').value);

            if (!isNaN(numerator)) {
                result = numerator; // Example calculation
                document.getElementById('result6').value = result.toFixed(2);
            } else {
                document.getElementById('result6').value = 'Invalid input';
            }
            break;
        case 7:
            numerator = parseFloat(document.getElementById('input13').value);
            if (!isNaN(numerator)) {
                result = numerator; // Example calculation
                document.getElementById('result7').value = result.toFixed(2);
            } else {
                document.getElementById('result7').value = 'Invalid input';
            }
            break;
        case 8:
            numerator = parseFloat(document.getElementById('input15').value);
            denominator = parseFloat(document.getElementById('input16').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator) * 100); // Example calculation
                document.getElementById('result8').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result8').value = 'Invalid input';
            }
            break;
        case 9:
            numerator = parseFloat(document.getElementById('input17').value);
            denominator = parseFloat(document.getElementById('input18').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = (((denominator-numerator)/denominator) * 100); // Example calculation
                document.getElementById('result9').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result9').value = 'Invalid input';
            }
            break;
       case 10:
      numerator = parseFloat(document.getElementById('input19').value);
            denominator = parseFloat(document.getElementById('input20').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator-denominator)/numerator)*100; // Example calculation
                document.getElementById('result10').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result10').value = 'Invalid input';
            }
        
    break;
        case 11:
            numerator = parseFloat(document.getElementById('input21').value);
            if (!isNaN(numerator)) {
                result = (numerator); // Example calculation
                document.getElementById('result11').value = result.toFixed(2);
            } else {
                document.getElementById('result11').value = 'Invalid input';
            }
            break;
        case 12:
            numerator = parseFloat(document.getElementById('input23').value);

            if (!isNaN(numerator)) {
                result = (numerator); // Example calculation
                document.getElementById('result12').value = result.toFixed(2);
            } else {
                document.getElementById('result12').value = 'Invalid input';
            }
            break;
        case 13:
            numerator = parseFloat(document.getElementById('input25').value);

            if (!isNaN(numerator)) {
                result = (numerator); // Example calculation
                document.getElementById('result13').value = result.toFixed(2);
            } else {
                document.getElementById('result13').value = 'Invalid input';
            }
            break;
        case 14:
            numerator = parseFloat(document.getElementById('input27').value);
            ;
            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result14').value = result.toFixed(2);
            } else {
                document.getElementById('result14').value = 'Invalid input';
            }
            break;
        case 15:
            numerator = parseFloat(document.getElementById('input29').value);

            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result15').value = result.toFixed(2);
            } else {
                document.getElementById('result15').value = 'Invalid input';
            }
            break;
        case 16:
            numerator = parseFloat(document.getElementById('input31').value);

            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result16').value = result.toFixed(2);
            } else {
                document.getElementById('result16').value = 'Invalid input';
            }
            break;
        case 17:
            numerator = parseFloat(document.getElementById('input33').value);

            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result17').value = result.toFixed(2);
            } else {
                document.getElementById('result17').value = 'Invalid input';
            }
            break;
        case 18:
            numerator = parseFloat(document.getElementById('input35').value);
            denominator = parseFloat(document.getElementById('input36').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator) * 100); // Example calculation
                document.getElementById('result18').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result18').value = 'Invalid input';
            }
            break;
        case 19:
            numerator = parseFloat(document.getElementById('input37').value);

            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result19').value = result.toFixed(2);
            } else {
                document.getElementById('result19').value = 'Invalid input';
            }
            break;
        case 20:
            numerator = parseFloat(document.getElementById('input39').value);
            denominator = parseFloat(document.getElementById('input40').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator) * 100); // Example calculation
                document.getElementById('result20').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result20').value = 'Invalid input';
            }
            break;
        case 21:
            numerator = parseFloat(document.getElementById('input41').value);
            denominator = parseFloat(document.getElementById('input42').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator) * 100); // Example calculation
                document.getElementById('result21').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result21').value = 'Invalid input';
            }
            break;
        case 22:
            numerator = parseFloat(document.getElementById('input43').value);
            denominator = parseFloat(document.getElementById('input42').value);
            if (!isNaN(numerator) && !isNaN(denominator) && denominator !== 0) {
                result = ((numerator / denominator) * 100);// Example calculation
                document.getElementById('result22').value = result.toFixed(2) + '%';
            } else {
                document.getElementById('result22').value = 'Invalid input';
            }
            break;
       case 23:
  const num = parseFloat(document.getElementById('input45').value);
  const den = parseFloat(document.getElementById('input46').value);

  if (isNaN(num) || isNaN(den)) {
    document.getElementById('result23').value = 'Invalid input';
  } else if (den === 0) {
    // Override logic: when denominator is zero, return 0%
    document.getElementById('result23').value = '0.00%';
  } else {
    const res = (num / den) * 100;
    document.getElementById('result23').value = res.toFixed(2) + '%';
  }
  break;


       case 24:
  const num24 = parseFloat(document.getElementById('input47').value);
  const den24 = parseFloat(document.getElementById('input48').value);

  if (isNaN(num24) || isNaN(den24)) {
    document.getElementById('result24').value = 'Invalid input';
  } else if (den24 === 0) {
    // When denominator is zero, return 0%
    document.getElementById('result24').value = '0.00%';
  } else {
    const res24 = (num24 / den24) * 100;
    document.getElementById('result24').value = res24.toFixed(2) + '%';
  }
  break;

        case 25:
            numerator = parseFloat(document.getElementById('input49').value);

            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result25').value = result.toFixed(2);
            } else {
                document.getElementById('result25').value = 'Invalid input';
            }
            break;
        case 26:
            numerator = parseFloat(document.getElementById('input51').value);

            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result26').value = result.toFixed(2);
            } else {
                document.getElementById('result26').value = 'Invalid input';
            }
            break;
        case 27:
            numerator = parseFloat(document.getElementById('input53').value);
            denominator = parseFloat(document.getElementById('input54').value);
            if (!isNaN(numerator)) {
                result = ((numerator/denominator)*100); // Example calculation
                document.getElementById('result27').value = result.toFixed(2);
            } else {
                document.getElementById('result27').value = 'Invalid input';
            }
            break;
        case 28:
            numerator = parseFloat(document.getElementById('input55').value);

            if (!isNaN(numerator)) {
                result = ((numerator)); // Example calculation
                document.getElementById('result28').value = result.toFixed(2);
            } else {
                document.getElementById('result28').value = 'Invalid input';
            }
            break;
        default:
            break; // Default case for invalid row numbers
    }
}
