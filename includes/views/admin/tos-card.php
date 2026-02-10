<?php if (!defined('ABSPATH')) exit; ?>

<!-- Terms of Service -->
<div class="pbay-card" id="pbay-tos-card" style="margin-top: 20px;">
    <div class="pbay-card-header">
        <div class="pbay-card-icon"><span class="dashicons dashicons-media-text"></span></div>
        <div>
            <h2>Terms of Service</h2>
            <p class="pbay-card-desc">Please read and accept before using PBay</p>
        </div>
    </div>

    <div id="pbay-tos-callout" class="pbay-callout <?php echo $tos_agreed ? 'pbay-callout-success' : 'pbay-callout-warning'; ?>" style="margin-bottom: 16px;">
        <span class="dashicons <?php echo $tos_agreed ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
        <div>
            <?php if ($tos_agreed): ?>
                <strong>Terms accepted</strong>
                You have agreed to the PBay Terms of Service.
            <?php else: ?>
                <strong>Acceptance required</strong>
                You must read and accept the Terms of Service before using PBay. Other pages are locked until you agree.
            <?php endif; ?>
        </div>
    </div>

    <p><a href="#" id="pbay-read-tos" class="button button-secondary">Read Terms of Service</a></p>

    <div class="pbay-tos-text" id="pbay-tos-text">
        <h3>Terms of Service</h3>
        <p><strong>PBay Marketplace Cardano Plugin</strong><br>Last Updated: 2/10/2026</p>
        <p>These Terms of Service (&ldquo;Terms&rdquo;) govern your use of the PBay Marketplace Cardano WordPress plugin and related open-source software (the &ldquo;Software&rdquo;), made available by the Provider (&ldquo;Provider,&rdquo; &ldquo;we,&rdquo; &ldquo;us,&rdquo; or &ldquo;our&rdquo;).</p>
        <p>By downloading, installing, or using the Software, you agree to these Terms.</p>

        <h3>1. Nature of the Software</h3>
        <p>The Software is an open-source WordPress plugin that enables users to create and operate their own self-hosted ecommerce systems that interact with the Cardano blockchain.</p>
        <p>The Software is a technical tool only. The Provider:</p>
        <ul>
            <li>Does not operate a marketplace</li>
            <li>Does not act as a buyer, seller, broker, agent, or intermediary</li>
            <li>Does not host user stores or listings</li>
            <li>Does not process, transmit, or hold funds for users</li>
            <li>Does not participate in any transactions between buyers and sellers</li>
        </ul>
        <p>All ecommerce activity occurs solely on infrastructure controlled by the user.</p>

        <h3>2. Non-Custodial Architecture</h3>
        <p>The Software enables users to generate and manage their own blockchain wallets and keys on infrastructure they control.</p>
        <p>The Provider:</p>
        <ul>
            <li>Does not receive, store, or have access to private keys, seed phrases, or wallet credentials</li>
            <li>Cannot access, move, freeze, or recover user funds</li>
            <li>Does not control blockchain transactions initiated by users</li>
        </ul>
        <p>Users are solely responsible for securely managing their wallets, keys, and blockchain activity.</p>
        <p>Loss of private keys, seed phrases, or wallet access is irreversible and the Provider cannot assist in recovery.</p>

        <h3>3. User Responsibility</h3>
        <p>You are solely responsible for:</p>
        <ul>
            <li>All goods and services you offer using the Software</li>
            <li>Compliance with all applicable laws and regulations</li>
            <li>Consumer protection, refunds, and dispute resolution</li>
            <li>Tax reporting and collection</li>
            <li>Product safety and regulatory compliance</li>
            <li>Shipping and fulfillment</li>
            <li>All blockchain transactions initiated from your systems</li>
        </ul>
        <p>You are the sole merchant of record for any transactions conducted using the Software.</p>

        <h3>4. Prohibited Uses</h3>
        <p>You may not use the Software for any unlawful or harmful purpose. This includes, but is not limited to, offering, selling, or facilitating:</p>
        <ul>
            <li>Illegal drugs or controlled substances</li>
            <li>Weapons or weapon components where prohibited by law</li>
            <li>Stolen goods</li>
            <li>Counterfeit or trademark-infringing goods</li>
            <li>Fraudulent schemes or deceptive services</li>
            <li>Hacking tools intended for unlawful use</li>
            <li>Personal data obtained unlawfully</li>
            <li>Human trafficking, exploitation, or abuse of any kind</li>
            <li>Transactions involving sanctioned individuals, entities, or jurisdictions</li>
            <li>Any goods or services whose sale violates applicable local, state, federal, or international law</li>
        </ul>
        <p>You are solely responsible for determining whether your use of the Software is lawful in your jurisdiction.</p>

        <h3>5. No Monitoring or Control</h3>
        <p>The Provider does not monitor, review, approve, or control:</p>
        <ul>
            <li>User websites</li>
            <li>Listings</li>
            <li>Transactions</li>
            <li>Wallet activity</li>
            <li>Blockchain interactions</li>
        </ul>
        <p>The Software is general-purpose infrastructure and the Provider has no knowledge of, or involvement in, specific user activity.</p>

        <h3>6. Open-Source and &ldquo;As-Is&rdquo; Software</h3>
        <p>The Software is provided free of charge under an open-source license.</p>
        <p>THE SOFTWARE IS PROVIDED &ldquo;AS IS&rdquo; AND &ldquo;AS AVAILABLE,&rdquo; WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO:</p>
        <ul>
            <li>MERCHANTABILITY</li>
            <li>FITNESS FOR A PARTICULAR PURPOSE</li>
            <li>NON-INFRINGEMENT</li>
            <li>ACCURACY OR RELIABILITY</li>
            <li>CONTINUOUS OR ERROR-FREE OPERATION</li>
        </ul>
        <p>Use of the Software is at your own risk.</p>

        <h3>7. Blockchain and Third-Party Systems</h3>
        <p>The Software interacts with decentralized networks and third-party wallet software that are not controlled by the Provider.</p>
        <p>The Provider is not responsible for:</p>
        <ul>
            <li>Blockchain network failures, congestion, or forks</li>
            <li>Wallet software errors</li>
            <li>Smart contract behavior</li>
            <li>Loss of funds due to user error</li>
            <li>Irreversible blockchain transactions</li>
        </ul>
        <p>All blockchain transactions are final and irreversible.</p>

        <h3>8. Limitation of Liability</h3>
        <p>To the maximum extent permitted by law, the Provider shall not be liable for any direct, indirect, incidental, consequential, special, punitive, or exemplary damages arising from or related to:</p>
        <ul>
            <li>Use or inability to use the Software</li>
            <li>Transactions conducted using the Software</li>
            <li>Goods or services sold by users</li>
            <li>Loss of profits, revenue, data, or digital assets</li>
            <li>Legal or regulatory actions resulting from user activity</li>
        </ul>
        <p>If liability is found to exist despite this limitation, the Provider&rsquo;s total liability shall not exceed $100 USD.</p>

        <h3>9. Indemnification</h3>
        <p>You agree to indemnify, defend, and hold harmless the Provider from any claims, damages, losses, liabilities, costs, and expenses (including legal fees) arising from:</p>
        <ul>
            <li>Your use of the Software</li>
            <li>Your sale of goods or services</li>
            <li>Your violation of these Terms</li>
            <li>Your violation of any law or regulation</li>
            <li>Any dispute between you and your customers or third parties</li>
        </ul>

        <h3>10. Termination</h3>
        <p>The Provider may, at its sole discretion, restrict access to updates, documentation, or related services if you violate these Terms.</p>
        <p>Because the Software is self-hosted and open-source, termination does not create any obligation for the Provider to disable your installation or access your systems.</p>

        <h3>11. Law Enforcement and Legal Compliance</h3>
        <p>The Provider may comply with lawful court orders, subpoenas, or legal processes. The Provider does not provide anonymity services and does not guarantee that use of the Software will shield users from legal accountability.</p>

        <h3>12. Governing Law</h3>
        <p>These Terms are governed by applicable law, without regard to conflict of law principles.</p>
        <p>Any disputes arising from these Terms shall be resolved in the appropriate jurisdiction.</p>

        <h3>13. Changes to These Terms</h3>
        <p>The Provider may update these Terms at any time. Continued use of the Software after changes are published constitutes acceptance of the revised Terms.</p>

        <h3>14. Contact</h3>
        <p>For questions regarding these Terms, contact: <a href="mailto:pb@pbdigitalmarketing.com">pb@pbdigitalmarketing.com</a></p>
    </div>

    <div class="pbay-tos-toggle">
        <label class="pbay-toggle-label">
            <input type="checkbox" id="pbay-agree-tos" <?php echo $tos_agreed ? 'checked' : 'disabled'; ?>>
            I have read and agree to the PBay Terms of Service
        </label>
    </div>
</div>
