/**
 * E-GABAY ASC - Print Reports Functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add click event to all print buttons
    const printButtons = document.querySelectorAll('.btn-print');
    if (printButtons) {
        printButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get report title from the button's data attribute or page title
                const reportTitle = this.getAttribute('data-report-title') || document.title;
                const reportDate = new Date().toLocaleDateString();
                const dateRange = document.querySelector('.date-range-text')?.textContent || '';
                
                // Create print header if it doesn't exist
                if (!document.querySelector('.print-header')) {
                    const header = document.createElement('div');
                    header.className = 'print-header print-only';
                    header.innerHTML = `
                        <h1>E-GABAY ASC</h1>
                        <h2>${reportTitle}</h2>
                        <p>Generated on: ${reportDate}</p>
                        ${dateRange ? `<p>${dateRange}</p>` : ''}
                    `;
                    
                    // Insert at the beginning of the content
                    const content = document.querySelector('.container-fluid');
                    if (content) {
                        content.insertBefore(header, content.firstChild);
                    }
                }
                
                // Add footer with page number if it doesn't exist
                if (!document.querySelector('.print-footer')) {
                    const footer = document.createElement('div');
                    footer.className = 'print-footer print-only';
                    footer.innerHTML = `
                        <p class="text-center mt-4">
                            E-GABAY ASC - Academic Support and Counseling System<br>
                            &copy; ${new Date().getFullYear()} All Rights Reserved
                        </p>
                    `;
                    
                    // Append to the end of the content
                    const content = document.querySelector('.container-fluid');
                    if (content) {
                        content.appendChild(footer);
                    }
                }
                
                // Wait for any charts to render completely
                setTimeout(() => {
                    window.print();
                }, 500);
            });
        });
    }
}); 